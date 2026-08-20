<?php

namespace Illuminate\Tests\App\Jobs;

class UniqueIdTestJob extends UniqueTestJob
{
    public function uniqueId(): string
    {
        return 'unique-id-1';
    }
}
