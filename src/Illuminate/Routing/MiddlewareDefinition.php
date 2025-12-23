<?php

namespace Illuminate\Routing;

use InvalidArgumentException;
use Stringable;

final readonly class MiddlewareDefinition implements Stringable
{
    /**
     * Create a new middleware definition instance.
     *
     * @param  string  $class
     * @param  array  $parameters
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public string $class,
        public array $parameters = [],
    ) {
        if (empty($class)) {
            throw new InvalidArgumentException('Middleware class cannot be empty.');
        }
    }

    /**
     * Convert the middleware definition to its string representation.
     */
    public function __toString(): string
    {
        if (empty($this->parameters)) {
            return $this->class;
        }

        $params = array_map(fn ($p) => (string) $p, array_values($this->parameters));

        return $this->class.':'.implode(',', $params);
    }
}
