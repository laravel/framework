<?php

namespace Illuminate\Tests\App\Jobs;

class UniqueTestRetryJob extends UniqueTestFailJob
{
    public $tries = 2;
}
