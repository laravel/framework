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

assertType('Illuminate\Routing\Route', $request->route());
assertType('object|string|null', $request->route('key'));

assertType('Symfony\Component\HttpFoundation\InputBag', $request->json());
assertType('mixed', $request->json('key'));
