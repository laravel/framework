<?php

declare(strict_types=1);

namespace Illuminate\Validation\Rules;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Stringable;
use UnitEnum;

class ArrayRule implements Stringable
{
    /**
     * The accepted keys.
     *
     * @var array
     */
    protected $keys;

    /**
     * Create a new in rule instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\BackedEnum|\UnitEnum|array|string|null  $values
     * @return void
     */
    public function __construct($keys = null)
    {
        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $this->keys = is_array($keys) ? $keys : func_get_args();
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if (! $this->keys) {
            return 'array';
        }

        $keys = array_map(
            static fn ($key) => match (true) {
                $key instanceof BackedEnum => $key->value,
                $key instanceof UnitEnum => $key->name,
                default => $key,
            },
            $this->keys,
        );

        return 'array:'.implode(',', $keys);
    }
}
