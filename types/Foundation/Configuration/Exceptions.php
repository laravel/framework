<?php

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Symfony\Component\HttpKernel\Exception\HttpException;

$exceptions = new Exceptions(
    new Handler(
        new Container,
    ),
);

$exceptions->stopIgnoring(HttpException::class);
$exceptions->stopIgnoring([ModelNotFoundException::class]);
