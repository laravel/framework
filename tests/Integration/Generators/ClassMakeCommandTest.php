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
            'public function __construct()',
        ], 'app/Reverb.php');
    }

    public function testItCanGenerateInvokableClassFile()
    {
        $this->artisan('make:class', ['name' => 'Notification', '--invokable' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'class Notification',
            'public function __construct()',
            'public function __invoke()',
        ], 'app/Notification.php');
    }
}
