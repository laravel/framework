<?php

use Illuminate\Database\Migrations\Migrator;

use function PHPStan\Testing\assertType;

/** @var Migrator $migrator */
$migrator = resolve(Migrator::class);

assertType("'foo'", $migrator->usingConnection('mysql', fn () => 'foo'));
