<?php

/** @var \Illuminate\Log\Logger $writer */
/** @var \Mockery\MockInterface|\Mockery\LegacyMockInterface|\Monolog\Logger $monolog */

$monolog->shouldReceive('error')->once()->with('foo', [
    '_where' => __FILE__ . ':10', // The number is place line no. call log
]);

$writer->error('foo'); // keep the code in the line
