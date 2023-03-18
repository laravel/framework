<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;

class CommandEventsTest extends TestCase
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
        $this->logfile = storage_path("logs/command_events_test_{$this->id}.log");

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
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app->make(ConsoleKernel::class)->rerouteSymfonyCommandEvents();
    }

    /**
     * @dataProvider commandEventsProvider
     */
    public function testCommandEventsReceiveParsedInput($processType, $argumentType)
    {
        $phpBinary = (new PhpExecutableFinder)->find();

        switch ($processType) {
            case 'foreground':
                $this->app[ConsoleKernel::class]->registerCommand(new CommandEventsTestCommand);
                $this->app[Dispatcher::class]->listen(function (CommandStarting $event) {
                    array_map(fn ($e) => $this->fs->append($this->logfile, $e."\n"), [
                        'CommandStarting',
                        $event->input->getArgument('firstname'),
                        $event->input->getArgument('lastname'),
                        $event->input->getOption('occupation'),
                    ]);
                });

                Event::listen(function (CommandFinished $event) {
                    array_map(fn ($e) => $this->fs->append($this->logfile, $e."\n"), [
                        'CommandFinished',
                        $event->input->getArgument('firstname'),
                        $event->input->getArgument('lastname'),
                        $event->input->getOption('occupation'),
                    ]);
                });
                switch ($argumentType) {
                    case 'array':
                        $this->artisan(CommandEventsTestCommand::class, [
                            'firstname' => 'taylor',
                            'lastname' => 'otwell',
                            '--occupation' => 'coding',
                        ]);
                        break;
                    case 'string':
                        $this->artisan('command-events-test-command taylor otwell --occupation=coding');
                        break;
                }
                break;
            case 'background':
                // Initialize empty logfile.
                $this->fs->append($this->logfile, '');

                Process::run($phpBinary.' '.base_path('artisan').' command-events-test-command-'.$this->id.' taylor otwell --occupation=coding');
                break;
        }

        $this->assertLogged(
            'CommandStarting', 'taylor', 'otwell', 'coding',
            'CommandFinished', 'taylor', 'otwell', 'coding',
        );
    }

    public static function commandEventsProvider()
    {
        return [
            'Foreground with array' => ['foreground', 'array'],
            'Foreground with string' => ['foreground', 'string'],
            'Background' => ['background', 'string'],
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
\$kernel->rerouteSymfonyCommandEvents();

class CommandEventsTestCommand extends Illuminate\Console\Command
{
    protected \$signature = 'command-events-test-command-{$this->id} {firstname} {lastname} {--occupation=cook}';

    public function handle()
    {
        // ...
    }
}

// Register command with Kernel
Illuminate\Console\Application::starting(function (\$artisan) {
    \$artisan->add(new CommandEventsTestCommand);
});

// Add command to scheduler so that the after() callback is trigger in our spawned process
Illuminate\Foundation\Application::getInstance()
    ->booted(function (\$app) {
        \$fs = new Illuminate\Filesystem\Filesystem;
        \$log = fn (\$msg) => \$fs->append({$logfile}, \$msg."\\n");

        \$app[\Illuminate\Events\Dispatcher::class]->listen(function (\Illuminate\Console\Events\CommandStarting \$event) use (\$log) {
            array_map(fn (\$msg) => \$log(\$msg), [
                'CommandStarting',
                \$event->input->getArgument('firstname'),
                \$event->input->getArgument('lastname'),
                \$event->input->getOption('occupation'),
            ]);
        });

        \$app[\Illuminate\Events\Dispatcher::class]->listen(function (\Illuminate\Console\Events\CommandFinished \$event) use (\$log) {
            array_map(fn (\$msg) => \$log(\$msg), [
                'CommandFinished',
                \$event->input->getArgument('firstname'),
                \$event->input->getArgument('lastname'),
                \$event->input->getOption('occupation'),
            ]);
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

class CommandEventsTestCommand extends \Illuminate\Console\Command
{
    protected $signature = 'command-events-test-command {firstname} {lastname} {--occupation=cook}';

    public function handle()
    {
        // ...
    }
}
