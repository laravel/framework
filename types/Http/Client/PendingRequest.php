<?php

use Illuminate\Support\Facades\Http;

use function PHPStan\Testing\assertType;

foreach (['get', 'post', 'put', 'patch', 'delete', 'head'] as $method) {
    assertType('Illuminate\Http\Client\Response', Http::createPendingRequest()->$method('/foo'));
    assertType('GuzzleHttp\Promise\PromiseInterface', Http::createPendingRequest()->async()->$method('/foo'));
}
