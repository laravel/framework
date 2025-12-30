<?php

use Illuminate\Support\Facades\Http;

use function PHPStan\Testing\assertType;

assertType('\Illuminate\Http\Client\Response', Http::createPendingRequest()->get('/foo'));
assertType('\GuzzleHttp\Promise\PromiseInterface', Http::async()->get('/foo'));
