<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Orchestra\Testbench\TestCase;

class AuthFacadeTest extends TestCase
{
    public function testItFailsIfTheUiPackageIsMissing()
    {
        $this->expectExceptionObject(new RuntimeException(
            'Please install the laravel/ui package in order to use the Auth::routes() method.'
        ));

        Auth::routes();
    }
}
