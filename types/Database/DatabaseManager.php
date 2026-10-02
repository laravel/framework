<?php

use Illuminate\Database\DatabaseManager;

use function PHPStan\Testing\assertType;

/** @var DatabaseManager $manager */
$manager = resolve(DatabaseManager::class);

assertType("'foo'", $manager->usingConnection('mysql', fn () => 'foo'));
