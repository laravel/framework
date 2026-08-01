<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Support\Arrayable;
use Stringable;

use function Illuminate\Support\enum_value;

class ArrayKeys implements Stringable
{
    /**
     * The accepted keys.
     *
     * @var array
     */
    protected $keys;

    /**
     * Create a new array keys rule instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $keys
     */
    public function __construct($keys)
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
        $keys = array_map(
            static fn ($key) => enum_value($key),
            $this->keys,
        );

        return 'array_keys:'.implode(',', $keys);
    }
}
