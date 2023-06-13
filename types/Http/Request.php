<?php

use Illuminate\Http\Request;

use function PHPStan\Testing\assertType;

class TestEnum
{
}

$request = Request::create('/', 'GET', [
    'key' => 'test',
]);

assertType('TestEnum|null', $request->enum('key', TestEnum::class));
