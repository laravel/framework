<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Foundation\Console\ViewClearCommand;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class CallCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Artisan::command('test:a', function () {
                $this->call('view:clear');
            });

            Artisan::command('test:b', function () {
                $this->call(ViewClearCommand::class);
            });

            Artisan::command('test:c', function () {
                $this->call($this->laravel->make(ViewClearCommand::class));
            });
        });

        parent::setUp();
    }

    #[TestWith(['test:a'])]
    #[TestWith(['test:b'])]
    #[TestWith(['test:c'])]
    public function testItCanCallCommands(string $command)
    {
        $this->artisan($command)->assertSuccessful();
    }
}
