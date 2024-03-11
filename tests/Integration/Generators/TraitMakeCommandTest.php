<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Generators;

class TraitMakeCommandTest extends TestCase
{
    public function testItCanGenerateTraitFile()
    {
        $this->artisan('make:trait', ['name' => 'FooTrait'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'trait FooTrait',
        ], 'app/FooTrait.php');
    }
}
