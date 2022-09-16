<?php

namespace Illuminate\Mail\Mailables;

class Address
{
    public $address;
    public $name;

    public function __construct(string $address, string $name = null)
    {
        $this->address = $address;
        $this->name = $name;
    }
}
