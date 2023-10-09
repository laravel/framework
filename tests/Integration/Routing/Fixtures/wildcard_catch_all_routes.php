<?php


namespace Test {
    function firstClassCallable(): string
    {
        return 'First class callable';
    }
}

namespace {
    use Illuminate\Support\Facades\Route;

    Route::get('/foo', function () {
        return 'Regular route';
    });


    Route::get('/baz', \Test\firstClassCallable(...));

    Route::get('/bag', ['Controller', 'method']);

    Route::get('{slug}', function () {
        return 'Wildcard route';
    });

}
