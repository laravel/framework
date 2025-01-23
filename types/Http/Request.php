<?php

use Illuminate\Http\Request;

use function PHPStan\Testing\assertType;

enum TestEnum: string
{
    case Foo = 'foo';
}

$request = Request::create('/', 'GET', [
    'key' => 'test',
]);

assertType('TestEnum|null', $request->enum('key', TestEnum::class));
