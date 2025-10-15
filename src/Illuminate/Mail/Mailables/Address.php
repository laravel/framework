<?php

namespace Illuminate\Mail\Mailables;

class Address
{
    /**
     * The recipient's email address.
     *
     * @var string
     */
    public $address;

    /**
     * The recipient's name.
     *
     * @var string|null
     */
    public $name;

    /**
     * Create a new address instance.
     */
    public function __construct(string $address, ?string $name = null)
    {
        $this->address = $address;
        $this->name = $name;
    }
}
