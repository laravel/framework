<?php

namespace Integration\Generators;

use Illuminate\Tests\Integration\Generators\TestCase;

class ClassMakeCommandTest extends TestCase
{
    public function testItCanGenerateClassFile()
    {
        $this->artisan('make:class', ['name' => 'Reverb'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'class Reverb',
        ], 'app/Reverb.php');
    }
}
