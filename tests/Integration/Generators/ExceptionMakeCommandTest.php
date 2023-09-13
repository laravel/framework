<?php

namespace Illuminate\Tests\Integration\Generators;

class ExceptionMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Exceptions/FooException.php',
    ];

    public function testItCanGenerateExceptionFile()
    {
        $this->artisan('make:exception', ['name' => 'FooException'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Exceptions;',
            'use Exception;',
            'class FooException extends Exception',
        ], 'app/Exceptions/FooException.php');

        $this->assertFileNotContains([
            'public function report()',
            'public function render($request)',
        ], 'app/Exceptions/FooException.php');
    }

    public function testItCanGenerateExceptionFileWithReportOption()
    {
        $this->artisan('make:exception', ['name' => 'FooException', '--report' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Exceptions;',
            'use Exception;',
            'class FooException extends Exception',
            'public function report()',
        ], 'app/Exceptions/FooException.php');

        $this->assertFileNotContains([
            'public function render($request)',
        ], 'app/Exceptions/FooException.php');
    }

    public function testItCanGenerateExceptionFileWithRenderOption()
    {
        $this->artisan('make:exception', ['name' => 'FooException', '--render' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Exceptions;',
            'use Exception;',
            'class FooException extends Exception',
            'public function render(Request $request): Response',
        ], 'app/Exceptions/FooException.php');

        $this->assertFileNotContains([
            'public function report()',
        ], 'app/Exceptions/FooException.php');
    }

    public function testItCanGenerateExceptionFileWithReportAndRenderOption()
    {
        $this->artisan('make:exception', ['name' => 'FooException', '--report' => true, '--render' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Exceptions;',
            'use Exception;',
            'class FooException extends Exception',
            'public function render(Request $request): Response',
            'public function report()',
        ], 'app/Exceptions/FooException.php');
    }
}
