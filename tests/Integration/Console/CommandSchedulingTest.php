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
    protected $logfile;

    /**
     * Just in case Testbench starts to ship an `artisan` script, we'll check and save a backup.
     *
     * @var string|null
     */
    protected $originalArtisan;

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
        $this->logfile = storage_path("logs/command_scheduling_test_{$this->id}.log");

        $this->writeArtisanScript();
    }

    protected function tearDown(): void
    {
        $this->fs->delete($this->logfile);
        $this->fs->delete(base_path('artisan'));

        if (! is_null($this->originalArtisan)) {
            $this->fs->put(base_path('artisan'), $this->originalArtisan);
        }

        parent::tearDown();
    }

    /**
     * @dataProvider executionProvider
     */
    public function testExecutionOrder($background, $expected)
    {
        $schedule = $this->app->make(Schedule::class);
        $event = $schedule
            ->command("test:{$this->id}")
            ->onOneServer()
            ->after(function () {
                $this->fs->append($this->logfile, "foreground:after\n");
            })
            ->before(function () {
                $this->fs->append($this->logfile, "foreground:before\n");
            });

        if ($background) {
            $event->runInBackground();
        }

        // We'll trigger the scheduler three times to simulate multiple servers
        $this->app->instance(Schedule::class, clone $schedule);
        $this->artisan('schedule:run');
        $this->app->instance(Schedule::class, clone $schedule);
        $this->artisan('schedule:run');
        $this->app->instance(Schedule::class, clone $schedule);
        $this->artisan('schedule:run');

        if ($background) {
            // Since our command is running in a separate process, we need to wait
            // until it has finished executing before running our assertions.
            $this->waitForLogMessages(...$expected);
        }

        $this->assertLogged(...$expected);
    }

    public static function executionProvider()
    {
        return [
            'Foreground' => [false, ['foreground:before', 'handled', 'foreground:after']],
            'Background' => [true, ['foreground:before', 'handled', 'background:after']],
        ];
    }

    protected function waitForLogMessages(...$messages)
    {
        $tries = 0;
        $sleep = 100000; // 100K microseconds = 0.1 second
        $limit = 50; // 0.1s * 50 = 5 second wait limit

        do {
            $log = $this->fs->get($this->logfile);

            if (Str::containsAll($log, $messages)) {
                return;
            }

            $tries++;
            usleep($sleep);
        } while ($tries < $limit);
    }

    protected function assertLogged(...$messages)
    {
        $log = trim($this->fs->get($this->logfile));

        $this->assertEquals(implode("\n", $messages), $log);
    }

    protected function writeArtisanScript()
    {
        $path = base_path('artisan');

        // Save existing artisan script if there is one
        if ($this->fs->exists($path)) {
            $this->originalArtisan = $this->fs->get($path);
        }

        $thisFile = __FILE__;
        $logfile = var_export($this->logfile, true);

        $script = <<<PHP
#!/usr/bin/env php
<?php

// This is a custom artisan script made specifically for:
//
// {$thisFile}
//
// It should be automatically cleaned up when the tests have finished executing.
// If you are seeing this file, an unexpected error must have occurred. Please
// manually remove it.

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../../autoload.php';

\$app = require_once __DIR__.'/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);

// Here is our custom command for the test
class CommandSchedulingTestCommand_{$this->id} extends Illuminate\Console\Command
{
    protected \$signature = 'test:{$this->id}';

    public function handle()
    {
        \$logfile = {$logfile};
        (new Illuminate\Filesystem\Filesystem)->append(\$logfile, "handled\\n");
    }
}

// Register command with Kernel
Illuminate\Console\Application::starting(function (\$artisan) {
    \$artisan->add(new CommandSchedulingTestCommand_{$this->id});
});

// Add command to scheduler so that the after() callback is trigger in our spawned process
Illuminate\Foundation\Application::getInstance()
    ->booted(function (\$app) {
        \$app->resolving(Illuminate\Console\Scheduling\Schedule::class, function(\$schedule) {
            \$fs = new Illuminate\Filesystem\Filesystem;
            \$schedule->command("test:{$this->id}")
                ->after(function() use (\$fs) {
                    \$logfile = {$logfile};
                    \$fs->append(\$logfile, "background:after\\n");
                })
                ->before(function() use (\$fs) {
                    \$logfile = {$logfile};
                    \$fs->append(\$logfile, "background:before\\n");
                });
        });
    });

\$status = \$kernel->handle(
    \$input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);

\$kernel->terminate(\$input, \$status);

exit(\$status);

PHP;

        $this->fs->put($path, $script);
    }
}
