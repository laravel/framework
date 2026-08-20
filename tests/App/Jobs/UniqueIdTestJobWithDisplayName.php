<?php

namespace Illuminate\Tests\App\Jobs;

class UniqueIdTestJobWithDisplayName extends UniqueTestJob
{
    public function uniqueId(): string
    {
        return 'unique-id-2';
    }

    public function displayName(): string
    {
        return 'App\\Actions\\UniqueTestAction';
    }
}
