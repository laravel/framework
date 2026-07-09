<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

use function PHPStan\Testing\assertType;

assertType('array', RouteFacade::get('/')->middleware());
assertType(Route::class, RouteFacade::get('/')->middleware('auth'));
assertType(Route::class, RouteFacade::get('/')->middleware(['auth']));
