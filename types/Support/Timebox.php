<?php

use Illuminate\Support\Timebox;

use function PHPStan\Testing\assertType;

assertType('1', (new Timebox)->call(function ($timebox) {
    assertType('Illuminate\Support\Timebox', $timebox);

    return 1;
}, 1));
