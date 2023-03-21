<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Tests\Integration\TestCase;
use RuntimeException;

class AuthFacadeTest extends TestCase
{
    public function testItFailsIfTheUiPackageIsMissing()
    {
        $this->expectExceptionObject(new RuntimeException(
            'In order to use the Auth::routes() method, please install the laravel/ui package.'
        ));

        Auth::routes();
    }
}
