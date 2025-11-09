<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('hello {name}', function ($name) {
    $this->info("Hello, $name!");
});
