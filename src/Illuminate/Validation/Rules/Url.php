<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Support\Arrayable;
use Stringable;

use function Illuminate\Support\enum_value;

class Url implements Stringable
{
    protected bool $active = false;
    
    /**
     * @var  string[]  $protocols
     */
    protected array $protocols = [];

    /**
     * Create a new array rule instance.
     *
     * @param  array|null  $protocols
     * @return void
     */
    public function __construct($protocols = null)
    {
        $this->protocols($protocols);
    }

    public function active(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @param  string[]|\Illuminate\Contracts\Support\Arrayable
     */
    public function protocols($protocols = null)
    {
        $this->protocols = match (true) {
            $keys instanceof Arrayable => $keys->toArray(),
            !is_array($keys) => func_get_args(),
            default => $keys,
        };

        return $this;
    }

    public function protocol(string $protocol)
    {
        $this->protocols = array_unique([ ...$this->protocols, $protocol ]);

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->active) {
            return 'active_url';
        }

        if ($this->protocols === []) {
            return 'url';
        }

        return 'url:'.implode(',', $this->protocols);
    }
}
