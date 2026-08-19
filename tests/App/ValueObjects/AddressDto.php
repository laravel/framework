<?php

namespace Illuminate\Tests\App\ValueObjects;

class AddressDto
{
    public function __construct(public string $lineOne, public string $lineTwo)
    {
        //
    }
}
