<?php

use Illuminate\Support\Traits\Localizable;

use function PHPStan\Testing\assertType;

$localizable = new class
{
    use Localizable;

    public function useWithLocale(): void
    {
        assertType("'foo'", $this->withLocale('en', fn () => 'foo'));
    }
};
