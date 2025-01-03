<?php

use Illuminate\Support\Fluent;

use function PHPStan\Testing\assertType;

$fluent = new Fluent(['name' => 'Taylor', 'age' => 25, 'user' => new User]);

assertType("Illuminate\Support\Fluent<string, 25|'Taylor'|User>", $fluent);
assertType('Illuminate\Support\Fluent<string, string>', new Fluent(['name' => 'Taylor']));
assertType('Illuminate\Support\Fluent<string, int>', new Fluent(['age' => 25]));
assertType('Illuminate\Support\Fluent<string, User>', new Fluent(['user' => new User]));

assertType("25|'Taylor'|User|null", $fluent['name']);
assertType("25|'Taylor'|User|null", $fluent['age']);
assertType("25|'Taylor'|User|null", $fluent['age']);
assertType("25|'Taylor'|User|null", $fluent->get('name'));
assertType("25|'Taylor'|User|null", $fluent->get('foobar'));
assertType("25|'Taylor'|'zonda'|User", $fluent->get('foobar', 'zonda'));
assertType("array<string, 25|'Taylor'|User>", $fluent->getAttributes());
assertType("array<string, 25|'Taylor'|User>", $fluent->toArray());
assertType("array<string, 25|'Taylor'|User>", $fluent->jsonSerialize());
