<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Process;
use Orchestra\Testbench\TestCase;

class DevCommandTest extends TestCase
{
    public function testBeforeCommandsRunInOrderAndStopOnFailure()
    {
        Process::fake([
            'php artisan migrate' => Process::result(),
            'php artisan optimize:clear' => Process::result(exitCode: 1),
        ])->preventStrayProcesses();

        DevCommands::before('php artisan migrate');
        DevCommands::before('php artisan optimize:clear');
        DevCommands::before('php artisan never-runs');

        $this->artisan('dev')
            ->expectsOutputToContain('The [php artisan optimize:clear] command failed.')
            ->assertFailed();

        Process::assertRanInOrder(['php artisan migrate', 'php artisan optimize:clear']);
    }
}
