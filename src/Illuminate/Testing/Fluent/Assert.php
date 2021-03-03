<?php

namespace Illuminate\Testing\Fluent;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Testing\AssertableJsonString;
use PHPUnit\Framework\Assert as PHPUnit;

class Assert implements Arrayable
{
    use Concerns\Has,
        Concerns\Matching,
        Concerns\Debugging,
        Concerns\Interaction,
        Macroable,
        Tappable;

    /** @var array */
    private $props;

    /** @var string */
    private $path;

    protected function __construct(array $props, string $path = null)
    {
        $this->path = $path;
        $this->props = $props;
    }

    protected function dotPath($key): string
    {
        if (is_null($this->path)) {
            return $key;
        }

        return implode('.', [$this->path, $key]);
    }

    protected function prop(string $key = null)
    {
        return Arr::get($this->props, $key);
    }

    protected function scope($key, Closure $callback): self
    {
        $props = $this->prop($key);
        $path = $this->dotPath($key);

        PHPUnit::assertIsArray($props, sprintf('Property [%s] is not scopeable.', $path));

        $scope = new self($props, $path);
        $callback($scope);
        $scope->interacted();

        return $this;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromAssertableJsonString(AssertableJsonString $json): self
    {
        return self::fromArray($json->json());
    }

    public function toArray()
    {
        return $this->props;
    }
}
