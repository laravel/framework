<?php

namespace Illuminate\Tests\Integration\Generators;

class ServiceMakeCommandTest extends TestCase
{
    protected $files = [
        'app/FooService.php',
    ];

    public function testItCanGenerateServiceFile()
    {
        $this->artisan('make:service', ['name' => 'FooService'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'class FooService',
        ], 'app/FooService.php');
    }
}
