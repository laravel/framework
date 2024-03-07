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
            'namespace App\Enums;',
            'enum StatusEnum',
        ], 'app/Enums/StatusEnum.php');
    }

    public function testItCanGenerateEnumFileWithString()
    {
        $this->artisan('make:enum', ['name' => 'StringEnum', '--string' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums;',
            'enum StringEnum: string',
        ], 'app/Enums/StringEnum.php');
    }

    public function testItCanGenerateEnumFileWithInt()
    {
        $this->artisan('make:enum', ['name' => 'IntEnum', '--int' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums;',
            'enum IntEnum: int',
        ], 'app/Enums/IntEnum.php');
    }
}
