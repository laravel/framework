<?php

namespace Illuminate\Mail\Mailables;

use InvalidArgumentException;

class Address
{
    /**
     * Create a new address instance.
     *
     * @param  string  $address
     * @param  string|null  $name
     */
    public function __construct(
        public string $address,
        public ?string $name = null,
    ) {
        if (preg_match('/[\r\n]/', $address) > 0) {
            throw new InvalidArgumentException('Email addresses may not contain line break characters.');
        }
    }
}
