<?php

namespace Illuminate\Tests\App\ValueObjects;

class AddressModel
{
    /**
     * @var string
     */
    public $lineOne;

    /**
     * @var string
     */
    public $lineTwo;

    public function __construct($address_line_one, $address_line_two)
    {
        $this->lineOne = $address_line_one;
        $this->lineTwo = $address_line_two;
    }
}
