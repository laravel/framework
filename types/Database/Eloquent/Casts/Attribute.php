<?php

use Illuminate\Database\Eloquent\Casts\Attribute;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType(
        'Illuminate\Database\Eloquent\Casts\Attribute<int, never>',
        Attribute::get(fn () => 1),
    );
    assertType(
        'Illuminate\Database\Eloquent\Casts\Attribute<never, string>',
        Attribute::set(fn (string $v) => ['foo' => $v]),
    );
    assertType(
        'Illuminate\Database\Eloquent\Casts\Attribute<int, string>',
        new Attribute(fn () => 1, fn (string $v) => ['foo' => $v]),
    );
}
