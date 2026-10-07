<?php

use Illuminate\Support\Facades\Route;

Route::query('/search', function () {
    return response()->json([
        'method' => request()->method(),
        'term' => request()->query('term'),
        'filter' => request()->input('filter'),
    ]);
});
