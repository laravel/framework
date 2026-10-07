<?php

use Illuminate\Support\Facades\Session;

use function PHPStan\Testing\assertType;

assertType('mixed', Session::get('key'));

assertType('string', Session::string('key'));
assertType('string', Session::string('key', 'default'));
assertType('int', Session::integer('key'));
assertType('int', Session::integer('key', 1));
assertType('float', Session::float('key'));
assertType('float', Session::float('key', 1.5));
assertType('bool', Session::boolean('key'));
assertType('bool', Session::boolean('key', true));
assertType('array', Session::array('key'));
assertType('Illuminate\Support\Collection', Session::collection('key'));

assertType('string', session()->string('key'));
assertType('int', session()->integer('key'));
