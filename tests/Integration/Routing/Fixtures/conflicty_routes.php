<?php

use Illuminate\Support\Facades\Route;

Route::any('/url', function () {
    return 'response from any';
});

Route::domain('territory')->match(['get', 'delete'], '/url', function () {
    return 'I am alien (?_?)';
});

Route::match(['get', 'post'], '/url', function () {
    return '2';
});

Route::match(['post'], '/url', function () {
    return '1';
});

Route::get('/{slug}', function () {
    return 'sluggish (-_-)zzz';
});
