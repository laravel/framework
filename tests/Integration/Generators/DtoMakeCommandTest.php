<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Tests\Integration\Generators\TestCase;

class DtoMakeCommandTest extends TestCase
{
    protected $files = [
        'app/DataTransferObjects/UserDto.php',
        'app/DataTransferObjects/Admin/TestDto.php',
    ];

    public function testItNotCanGenerateDtoFile()
    {
        $this->artisan('make:dto', ['name' => 'UserDto'])
            ->assertExitCode(0);
    }
}

