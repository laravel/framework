<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\BroadcastingInstallCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class BroadcastingInstallCommandTest extends TestCase
{
    protected string $basePath;

    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->basePath = sys_get_temp_dir().'/laravel-framework-broadcasting-install-command-test-'.bin2hex(random_bytes(5));

        $this->files->ensureDirectoryExists($this->basePath.'/bootstrap');
        $this->files->ensureDirectoryExists($this->basePath.'/config');
        $this->files->ensureDirectoryExists($this->basePath.'/routes');
        $this->files->ensureDirectoryExists($this->basePath.'/resources/js');

        $this->files->put($this->basePath.'/routes/web.php', '<?php');
        $this->files->put($this->basePath.'/routes/console.php', '<?php');
        $this->files->put($this->basePath.'/.env', "APP_NAME=Laravel\n");

        $this->files->put($this->basePath.'/resources/js/bootstrap.js', <<<'JS'
import axios from 'axios';
window.axios = axios;
JS
        );

        $this->files->put($this->basePath.'/bootstrap/app.php', <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function testItInstallsBroadcastingScaffolding()
    {
        $command = $this->newCommand();

        $this->runCommand($command, [
            '--reverb' => true,
            '--without-reverb' => true,
            '--without-node' => true,
        ]);

        $bootstrap = file_get_contents($this->basePath.'/bootstrap/app.php');
        $env = file_get_contents($this->basePath.'/.env');
        $channels = file_get_contents($this->basePath.'/routes/channels.php');
        $echo = file_get_contents($this->basePath.'/resources/js/echo.js');
        $bootstrapJs = file_get_contents($this->basePath.'/resources/js/bootstrap.js');

        $this->assertFileExists($this->basePath.'/config/broadcasting.php');
        $this->assertFileExists($this->basePath.'/routes/channels.php');
        $this->assertStringContainsString("channels: __DIR__.'/../routes/channels.php',", $bootstrap);
        $this->assertStringNotContainsString('// channels: ', $bootstrap);
        $this->assertStringContainsString('BROADCAST_CONNECTION=reverb', $env);
        $this->assertStringContainsString('Broadcast::channel', $channels);
        $this->assertStringContainsString("broadcaster: 'reverb'", $echo);
        $this->assertStringContainsString("import './echo'", $bootstrapJs);
    }

    public function testItDoesNotDuplicateChannelsRouteRegistration()
    {
        $command = $this->newCommand();

        $this->runCommand($command, [
            '--reverb' => true,
            '--without-reverb' => true,
            '--without-node' => true,
        ]);

        $this->runCommand($command, [
            '--reverb' => true,
            '--without-reverb' => true,
            '--without-node' => true,
        ]);

        $bootstrap = file_get_contents($this->basePath.'/bootstrap/app.php');

        $this->assertSame(1, substr_count($bootstrap, "channels: __DIR__.'/../routes/channels.php',"));
    }

    public function testItInsertsChannelsRouteWhenLegacyBootstrapFormatIsUsed()
    {
        $this->files->put($this->basePath.'/bootstrap/app.php', <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
PHP
        );

        $command = $this->newCommand();

        $this->runCommand($command, [
            '--reverb' => true,
            '--without-reverb' => true,
            '--without-node' => true,
        ]);

        $bootstrap = file_get_contents($this->basePath.'/bootstrap/app.php');

        $this->assertStringContainsString("channels: __DIR__.'/../routes/channels.php',", $bootstrap);
    }

    protected function newCommand(): BroadcastingInstallCommand
    {
        $command = new class extends BroadcastingInstallCommand
        {
            public function call($command, $arguments = [], $outputBuffer = null)
            {
                if ($command === 'config:publish') {
                    $destination = $this->laravel->configPath('broadcasting.php');

                    if (! file_exists($destination)) {
                        (new Filesystem)->ensureDirectoryExists(dirname($destination));

                        copy($this->frameworkPath('config/broadcasting.php'), $destination);
                    }

                    return 0;
                }

                return parent::call($command, $arguments, $outputBuffer);
            }

            protected function frameworkPath(string $path): string
            {
                return dirname(__DIR__, 3).'/'.$path;
            }

            protected function installReverb(): void
            {
                //
            }

            protected function installNodeDependencies(): void
            {
                //
            }

            protected function installDriverPackages(): void
            {
                //
            }
        };

        $command->setLaravel(new Application($this->basePath));

        return $command;
    }

    protected function runCommand(BroadcastingInstallCommand $command, array $options = []): void
    {
        $command->run(new ArrayInput($options), new BufferedOutput);
    }
}
