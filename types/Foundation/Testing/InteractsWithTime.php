<?php

namespace Illuminate\Types\Foundation\Testing;

use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;
use Illuminate\Support\Carbon;

use function PHPStan\Testing\assertType;

class InteractsWithTimeTestCase
{
    use InteractsWithTime;

    public function test(): void
    {
        assertType(Carbon::class, $this->freezeTime());
        assertType('42', $this->freezeTime(fn () => 42));

        assertType(Carbon::class, $this->freezeSecond());
        assertType('42', $this->freezeSecond(fn () => 42));

        // @phpstan-ignore method.void
        assertType('null', $this->travelTo(Carbon::now(), function () {
        }));
        assertType('42', $this->travelTo(Carbon::now(), fn () => 42));
    }
}
