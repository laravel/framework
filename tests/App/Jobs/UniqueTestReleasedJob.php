<?php

namespace Illuminate\Tests\App\Jobs;

class UniqueTestReleasedJob extends UniqueTestFailJob
{
    public $tries = 1;

    public function handle()
    {
        static::$handled = true;

        $this->release();
    }
}
