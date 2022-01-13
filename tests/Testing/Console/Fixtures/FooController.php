<?php

namespace Illuminate\Tests\Testing\Console\Fixtures;

use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Controller;

class FooController extends Controller
{
    public function show(User $user)
    {
        // ..
    }

    public function __invoke()
    {
        // ..
    }
}
