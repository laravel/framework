<?php

use Illuminate\Support\Stringable;

use function PHPStan\Testing\assertType;

$stringable = new Stringable();

assertType('Illuminate\Support\Collection<int, string>', $stringable->explode(''));

assertType('Illuminate\Support\Collection<int, string>', $stringable->split(1));

assertType('Illuminate\Support\Collection<int, string>', $stringable->ucsplit());
