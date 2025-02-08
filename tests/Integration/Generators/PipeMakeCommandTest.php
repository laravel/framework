<?php

namespace Illuminate\Tests\Integration\Generators;

class PipeMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Pipes/FooPipe.php',
        'tests/Feature/Pipes/FooPipeTest.php',
    ];

    public function testItCanGeneratePipeFile()
    {
        $this->artisan('make:pipe', ['name' => 'FooPipe'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Pipes;',
            'class FooPipe',
        ], 'app/Pipes/FooPipe.php');
    }
}
