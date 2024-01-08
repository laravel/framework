<?php

namespace Illuminate\Tests\Integration\Generators;

class RefinerMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Http/Refiners/Foo.php',
    ];

    public function testItCanGenerateCastFile()
    {
        $this->artisan('make:refiner', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Http\Refiners;',
            'use Illuminate\Refine\Refiner;',
            'class Foo extends Refiner',
            'public function __construct()',
        ], 'app/Http/Refiners/Foo.php');
    }
}
