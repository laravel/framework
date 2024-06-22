<?php

use Illuminate\Support\Facades\Route;

Route::get('/admin/{user}', fn () => response('', 200))->name('admin.user');
