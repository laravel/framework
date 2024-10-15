<?php

use Illuminate\Http\Request;

use function PHPStan\Testing\assertType;

/**
 * @var \Illuminate\Contracts\Container\Container $container
 */
assertType('Illuminate\Http\Request', $container->instance('request', Request::capture()));
