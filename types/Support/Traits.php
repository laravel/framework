<?php

use Illuminate\Support\Traits\Conditionable;
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

$conditionable = new class
{
    use Conditionable;

    public function useWhenNotNull(?int $nullableValue): void
    {
        $this->whenNotNull($nullableValue, function ($conditionable, $value) {
            assertType('int', $value);
        }, function ($conditionable, $value) {
            assertType('null', $value);
        });

        $this->whenNotNull(fn () => $nullableValue, function ($conditionable, $value) {
            assertType('int', $value);
        });
    }
};
