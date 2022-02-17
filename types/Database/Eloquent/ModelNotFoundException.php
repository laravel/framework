<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use function PHPStan\Testing\assertType;

class Foo extends Model {}

$modelNotFound = new ModelNotFoundException();

assertType('array<int|string>', $modelNotFound->getIds());
assertType('class-string<Illuminate\Database\Eloquent\Model>', $modelNotFound->getModel());
assertType('Illuminate\Database\Eloquent\Model', new ($modelNotFound->getModel()));

$modelNotFound->setModel(Foo::class, 1);
$modelNotFound->setModel(Foo::class, [1]);
$modelNotFound->setModel(Foo::class, '1');
$modelNotFound->setModel(Foo::class, ['1']);
