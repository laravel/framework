<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class CommandSchedulingTest extends TestCase
{
    /**
     * Each run of this test is assigned a random ID to ensure that separate runs
     * do not interfere with each other.
     *
     * @var string
     */
    protected $id;

    /**
     * The path to the file that execution logs will be written to.
     *
     * @var string
     */
    protected $logFile;

    /**
     * The path to the custom command that will be written for this test.
     *
     * @var string
     */
    protected $commandFile;

    /**
     * The Filesystem instance for writing stubs and logs.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $fs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fs = new Filesystem;

        $this->id = Str::random();
        $this->logFile = storage_path("logs/command_scheduling_test_{$this->id}.log");
        $this->commandFile = app_path("Console/Commands/CommandSchedulingTestCommand_{$this->id}.php");

        // We always need to clean this up in case of an Exception in a previous run,
        // since Test Bench will automatically include it when we execute a command.
        $this->fs->delete($this->app->basePath('routes/console.php'));

        $this->writeTestStubs();
    }

    protected function tearDown(): void
    {
        $this->fs->delete($this->logFile);
        $this->fs->delete(base_path('artisan'));
        $this->fs->delete(base_path('routes/console.php'));
        $this->fs->delete($this->commandFile);

        parent::tearDown();
    }

    /**
     * @dataProvider executionProvider
     */
    public function testExecutionOrder($background)
    {
        $event = $this->app->make(Schedule::class)
            ->command("test:{$this->id}")
            ->onOneServer()
            ->after(function () {
                $this->fs->append($this->logFile, "after\n");
            })
            ->before(function () {
                $this->fs->append($this->logFile, "before\n");
            });

        if ($background) {
            $event->runInBackground();
        }

        // We'll trigger the scheduler three times to simulate multiple servers
        $this->artisan('schedule:run');
        $this->artisan('schedule:run');
        $this->artisan('schedule:run');

        if ($background) {
            // Since our command is running in a separate process, we need to wait
            // until it has finished executing before running our assertions.
            $this->waitForLogMessages('before', 'handled', 'after');
        }

        $this->assertLogged('before', 'handled', 'after');
    }

    public function executionProvider()
    {
        return [
            'Foreground' => [false],
            'Background' => [true],
        ];
    }

    protected function waitForLogMessages(...$messages)
    {
        $tries = 0;
        $sleep = 100000; // 100K microseconds = 0.1 second
        $limit = 50; // 0.1s * 50 = 5 second wait limit

        do {
            $log = $this->fs->get($this->logFile);

            if (Str::containsAll($log, $messages)) {
                return;
            }

            $tries++;
            usleep($sleep);
        } while ($tries < $limit);
    }

    protected function assertLogged(...$messages)
    {
        $log = trim($this->fs->get($this->logFile));

        $this->assertEquals(implode("\n", $messages), $log);
    }

    protected function writeTestStubs()
    {
        // Make sure our target directories exist
        $this->fs->ensureDirectoryExists(app_path('Console/Commands'));
        $this->fs->ensureDirectoryExists(base_path('routes'));

        // Get PHP-ready representation of our paths
        $logFile = var_export($this->logFile, true);
        $commandFile = var_export($this->commandFile, true);

        // Testbench doesn't ship with an artisan script, so we need to add one to the project
        // so that we can execute commands in a separate process
        $artisan = <<<PHP
#!/usr/bin/env php
<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../../autoload.php';

\$app = require_once __DIR__.'/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);

\$status = \$kernel->handle(
    \$input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);

\$kernel->terminate(\$input, \$status);

exit(\$status);

PHP;

        // Each time we run the test we need to write to a unique log file to ensure that separate
        // test runs don't pollute the results of each other
        $command = <<<PHP
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CommandSchedulingTestCommand_{$this->id} extends Command
{
    protected \$signature = 'test:{$this->id}';

    public function handle()
    {
        \$logfile = {$logFile};
        (new Filesystem)->append(\$logfile, "handled\\n");
    }
}
PHP;

        // Testbench automatically loads `routes/console.php` on initialization of the console
        // Kernel, so this is a convenient place to register out command and set up the scheduler
        $route = <<<PHP
<?php

require_once {$commandFile};

// Register command with Kernel
Illuminate\Console\Application::starting(function (\$artisan) {
    \$artisan->add(new App\Console\Commands\CommandSchedulingTestCommand_{$this->id});
});

// Add command to scheduler so that the after() callback is trigger in our spawned process
Illuminate\Foundation\Application::getInstance()
    ->booted(function (\$app) {
        \$app->resolving(Illuminate\Console\Scheduling\Schedule::class, function(\$schedule) {
            \$fs = new Illuminate\Filesystem\Filesystem;
            \$schedule->command("test:{$this->id}")
                ->after(function() use (\$fs) {
                    \$logfile = {$logFile};
                    \$fs->append(\$logfile, "after\\n");
                })
                ->before(function() use (\$fs) {
                    \$logfile = {$logFile};
                    \$fs->append(\$logfile, "before\\n");
                });
        });
    });

PHP;

        $this->fs->put($this->commandFile, $command);
        $this->fs->put(base_path('routes/console.php'), $route);
        $this->fs->put(base_path('artisan'), $artisan);
    }
}
