<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        Log::info('Fetched message from queue: '.$this->message);
    }
}
