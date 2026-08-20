<?php

namespace Illuminate\Tests\App\Jobs;

class UniqueTestJobWithDisplayName extends UniqueTestJob
{
    public function displayName(): string
    {
        return 'App\\Actions\\UniqueTestAction';
    }
}
