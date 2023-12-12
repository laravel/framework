<?php

use Illuminate\Foundation\Console\AboutCommand;

use function PHPStan\Testing\assertType;

$format = AboutCommand::format(true, console: fn ($value) => $value ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF');

assertType('Closure(bool): mixed', $format);
assertType('mixed', $format(false));
assertType('mixed', $format(true));
