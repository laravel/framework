<?php

namespace Illuminate\Tests\App\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Tests\App\Casts\EuroCaster;

class Euro implements Castable
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function castUsing(array $arguments)
    {
        return EuroCaster::class;
    }
}
