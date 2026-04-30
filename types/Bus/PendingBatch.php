<?php

use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

use function PHPStan\Testing\assertType;

/** @var PendingBatch $pendingBatch */
$pendingBatch = Bus::batch([]);

assertType('Illuminate\Bus\PendingBatch', $pendingBatch);
assertType('Illuminate\Bus\PendingBatch', $pendingBatch->onQueue('queue'));
assertType('Illuminate\Bus\PendingBatch', $pendingBatch->name('batch-name'));
assertType('Illuminate\Bus\PendingBatch', $pendingBatch->allowFailures());

assertType('Illuminate\Bus\Batch', $pendingBatch->dispatch());
assertType('Illuminate\Bus\Batch', $pendingBatch->dispatchAfterResponse());

assertType('Illuminate\Bus\Batch|null', $pendingBatch->dispatchIf(true));
assertType('Illuminate\Bus\Batch|null', $pendingBatch->dispatchUnless(false));

/** @var Batch $batch */
$batch = $pendingBatch->dispatch();

assertType('Illuminate\Bus\Batch|null', $batch->fresh());
assertType('Illuminate\Bus\Batch|null', $batch->add([]));
assertType('Illuminate\Bus\Batch', $batch->addOrFail([]));

assertType('Illuminate\Bus\Batch|null', Bus::findBatch('some-id'));
