<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class CustomPayloadJob implements ShouldQueue
{
    public $connection = 'sync';

    public function handle()
    {
        //
    }
}
