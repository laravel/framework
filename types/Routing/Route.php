<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

use function PHPStan\Testing\assertType;

assertType('array', RouteFacade::get('/')->middleware());
assertType(Route::class, RouteFacade::get('/')->middleware('auth'));
assertType(Route::class, RouteFacade::get('/')->middleware(['auth']));

assertType('string|null', RouteFacade::get('/')->domain());
assertType(Route::class, RouteFacade::get('/')->domain('example.com'));

assertType('array<string, mixed>', RouteFacade::get('/')->getMetadata());
assertType('mixed', RouteFacade::get('/')->getMetadata('key'));
