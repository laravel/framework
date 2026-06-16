<?php

use Illuminate\Support\Traits\Localizable;
use Illuminate\Support\UriQueryString;

use function PHPStan\Testing\assertType;

$localizable = new class
{
    use Localizable;

    public function useWithLocale(): void
    {
        assertType("'foo'", $this->withLocale('en', fn () => 'foo'));
    }
};

$interactsWithData = function (UriQueryString $query): void {
    assertType('1|2|Illuminate\Support\UriQueryString', $query->whenEnum('foo', TestIntEnum::class, function ($enum) {
        assertType('TestIntEnum', $enum);

        return 1;
    }, function () {
        return 2;
    }));

    assertType('3|Illuminate\Support\UriQueryString', $query->whenEnum('foo', TestIntEnum::class, function ($enum) {
        return 3;
    }));
};

enum TestIntEnum: int
{
}
