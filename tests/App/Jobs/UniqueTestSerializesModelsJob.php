<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Foundation\Auth\User;
use Illuminate\Queue\SerializesModels;

class UniqueTestSerializesModelsJob extends UniqueTestJob
{
    use SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(public User $user)
    {
    }
}
