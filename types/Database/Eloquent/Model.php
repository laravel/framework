<?php

use function PHPStan\Testing\assertType;

$factory = User::factory();
assertType('Illuminate\Database\Eloquent\Factories\Factory<User>', $factory);
