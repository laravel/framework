<?php

namespace Illuminate\Tests\Integration\Generators;

class ClassMakeCommandTest extends TestCase
{
    protected array $files = [
        'app/Reverb.php',
        'app/Notification.php'
    ];

    public function testItCanGenerateClassFile(): void
    {
        $this->artisan('make:class', ['name' => 'Reverb'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'class Reverb',
            'public function __construct()',
        ], 'app/Reverb.php');
    }

    public function testItCanGenerateStrictClassFile(): void
    {
        $this->artisan('make:class', ['name' => 'Reverb', '--strict' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'declare(strict_types=1)',
            'namespace App;',
            'class Reverb',
            'public function __construct()',
        ], 'app/Reverb.php');
    }

    public function testItCanGenerateInvokableClassFile(): void
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

    public function testItCanGenerateStrictInvokableClassFile(): void
    {
        $this->artisan('make:class', ['name' => 'Notification', '--invokable' => true, '--strict' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'declare(strict_types=1)',
            'namespace App;',
            'class Notification',
            'public function __construct()',
            'public function __invoke()',
        ], 'app/Notification.php');
    }
}
