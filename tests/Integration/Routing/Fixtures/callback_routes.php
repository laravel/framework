<?php

use Illuminate\Support\Facades\Route;

Route::get('/foo/{bar}', function () {
    return 'Regular matched route';
})->where('bar', function ($bar) {
    return $bar === 'baz';
});

Route::get('{slug}', function () {
    return 'Wildcard matched route';
})->where('slug', function ($slug) {
    return in_array($slug, ['bar', 'baz']);
});
