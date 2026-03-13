<?php

namespace Illuminate\Mail\Mailables;

class Address
{
    /**
     * Create a new address instance.
     */
    public function __construct(
        public string $address,
        public ?string $name = null,
    ) {
    }
}
