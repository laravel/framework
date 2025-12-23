<?php

namespace Illuminate\Routing;

use InvalidArgumentException;
use Stringable;

final readonly class MiddlewareDefinition implements Stringable
{
    /**
     * Create a new middleware definition instance.
     *
     * @param  class-string  $class
     * @param  array  $parameters
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public readonly string $class,
        public readonly array $parameters = [],
    ) {
        if (empty($class)) {
            throw new InvalidArgumentException('Middleware class cannot be empty.');
        }
    }

    /**
     * Determine if the parameters are named (associative array).
     */
    public function hasNamedParameters(): bool
    {
        if (empty($this->parameters)) {
            return false;
        }

        return array_keys($this->parameters) !== range(0, count($this->parameters) - 1);
    }

    /**
     * Convert the middleware definition to the legacy string format.
     */
    public function __toString(): string
    {
        if (empty($this->parameters)) {
            return $this->class;
        }

        $params = $this->hasNamedParameters()
            ? array_values($this->parameters)
            : $this->parameters;

        $params = array_map(fn ($p) => (string) $p, $params);

        return $this->class.':'.implode(',', $params);
    }
}
