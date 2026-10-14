<?php

use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;

use function PHPStan\Testing\assertType;

/** @var PendingBatch $batch */
$batch = resolve(PendingBatch::class);

assertType('Illuminate\Bus\PendingBatch', $batch->allowFailures());
assertType('Illuminate\Bus\PendingBatch', $batch->allowFailures(false));
assertType('Illuminate\Bus\PendingBatch', $batch->allowFailures(function (Batch $batch, ?Throwable $exception): void {
}));
assertType('Illuminate\Bus\PendingBatch', $batch->allowFailures([
    function (Batch $batch, ?Throwable $exception): void {
    },
    fn (Batch $batch) => null,
]));
