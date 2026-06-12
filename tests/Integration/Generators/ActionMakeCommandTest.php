<?php

namespace Illuminate\Tests\Integration\Generators;

class ActionMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Actions/CreateInvoice.php',
        'app/Actions/Billing/CreateInvoice.php',
    ];

    public function testItCanGenerateActionFile(): void
    {
        $this->artisan('make:action', ['name' => 'CreateInvoice'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Actions;',
            'class CreateInvoice',
            'public function handle(): void',
        ], 'app/Actions/CreateInvoice.php');
    }

    public function testItCanGenerateNestedActionFile(): void
    {
        $this->artisan('make:action', ['name' => 'Billing/CreateInvoice'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Actions\Billing;',
            'class CreateInvoice',
            'public function handle(): void',
        ], 'app/Actions/Billing/CreateInvoice.php');
    }
}
