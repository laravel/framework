<?php

namespace Illuminate\Encryption;

class Key
{
    /**
     * Create a new Key instance.
     *
     * @param  string  $value
     * @return void
     */
    public function __construct(private string $value)
    {
    }

    /**
     * Get the key value.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
