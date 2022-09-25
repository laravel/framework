<?php

use Illuminate\Http\Request;
use function PHPStan\Testing\assertType;

enum TestEnum : string
{
    case test = 'test';
}

$request = Request::create('/', 'GET', [
    'key' => 'test'
]);
assertType('TestEnum|null', $request->enum('key', TestEnum::class));
