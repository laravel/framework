<?php

namespace Integration\Generators;

use Illuminate\Tests\Integration\Generators\TestCase;

class EnumMakeCommandTest extends TestCase
{
    public function testItCanGenerateEnumFile()
    {
        $this->artisan('make:enum', ['name' => 'StatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'enum StatusEnum',
        ], 'app/StatusEnum.php');
    }
}
