<?php

use Illuminate\Support\Fluent;

use function PHPStan\Testing\assertType;

$fluent = new Fluent(['name' => 'Taylor', 'age' => 25, 'user' => new User]);

assertType('Illuminate\Support\Fluent<string, int|string|User>', $fluent);
assertType('Illuminate\Support\Fluent<string, string>', new Fluent(['name' => 'Taylor']));
assertType('Illuminate\Support\Fluent<string, int>', new Fluent(['age' => 25]));
assertType('Illuminate\Support\Fluent<string, User>', new Fluent(['user' => new User]));

assertType('int|string|User|null', $fluent['name']);
assertType('int|string|User|null', $fluent['age']);
assertType('int|string|User|null', $fluent['age']);
assertType('int|string|User|null', $fluent->get('name'));
assertType('int|string|User|null', $fluent->get('foobar'));
assertType('int|string|User', $fluent->get('foobar', 'zonda'));
assertType('array<string, int|string|User>', $fluent->getAttributes());
assertType('array<string, int|string|User>', $fluent->toArray());
assertType('array<string, int|string|User>', $fluent->jsonSerialize());
