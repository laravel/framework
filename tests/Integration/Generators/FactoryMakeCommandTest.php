<?php

namespace Illuminate\Tests\Integration\Generators;

class FactoryMakeCommandTest extends TestCase
{
    protected $files = [
        'database/factories/FooFactory.php',
    ];

    public function testItCanGenerateFactoryFile()
    {
        $this->artisan('make:factory', ['name' => 'FooFactory'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Database\Factories;',
            'use Illuminate\Database\Eloquent\Factories\Factory;',
            'class FooFactory extends Factory',
            'public function definition()',
        ], 'database/factories/FooFactory.php');
    }
}
