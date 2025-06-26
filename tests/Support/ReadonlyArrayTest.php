<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Support\ToReadonlyArray;
use Illuminate\Support\Traits\CastsToReadonlyArray;
use PHPUnit\Framework\TestCase;

class ReadonlyArrayTest extends TestCase
{
    public function testToReadonlyArray()
    {
        $dto = new class() implements ToReadonlyArray
        {
            use CastsToReadonlyArray;

            public function __construct(
                public readonly string $name = 'Zoran',
                public readonly int $age = 33
            ) {
            }
        };

        $this->assertEquals([
            'name' => 'Zoran',
            'age' => 33,
        ], $dto->toReadonlyArray());
    }
}
