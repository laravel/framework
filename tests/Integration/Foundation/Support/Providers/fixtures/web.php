<?php

use Illuminate\Support\Facades\Route;

Route::get('/{user}', fn () => response('', 404));
