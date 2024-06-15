<?php

use function PHPStan\Testing\assertType;

assertType('Illuminate\Database\Eloquent\Factories\Factory<User>', User::factory());

assertType('Illuminate\Database\Eloquent\Builder<User>', User::query());
