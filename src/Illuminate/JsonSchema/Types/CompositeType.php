<?php

namespace Illuminate\JsonSchema\Types;

use InvalidArgumentException;

class CompositeType extends Type
{
    public const ALL_OF = 'allOf';

    public const ANY_OF = 'anyOf';

    public const ONE_OF = 'oneOf';

    /**
     * Create a new composite type instance.
     *
     * @param  'allOf'|'anyOf'|'oneOf'  $keyword
     * @param  array<int, Type>  $schemas
     */
    public function __construct(protected string $keyword, protected array $schemas)
    {
        if (! in_array($keyword, [self::ALL_OF, self::ANY_OF, self::ONE_OF], true)) {
            throw new InvalidArgumentException("Unsupported [{$keyword}] composite keyword.");
        }

        $this->schemas = array_values($schemas);
    }

    /**
     * Get the composite keyword.
     */
    public function keyword(): string
    {
        return $this->keyword;
    }

    /**
     * Get the composite schemas.
     *
     * @return array<int, Type>
     */
    public function schemas(): array
    {
        return $this->schemas;
    }
}
