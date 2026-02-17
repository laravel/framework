<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Support\Facades\Vite;
use Orchestra\Testbench\TestCase;

class TestCaseTest extends TestCase
{
    public function testWithoutViteClearFacadeResolvedInstance()
    {
        Vite::useScriptTagAttributes([
            'crossorigin' => 'anonymous',
        ]);

        $this->withoutVite();

        Vite::asset('foo.png');
    }
}
