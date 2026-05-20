<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\ApiInstallCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ApiInstallCommandTest extends TestCase
{
    protected string $basePath;

    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->basePath = sys_get_temp_dir().'/laravel-framework-api-install-command-test-'.bin2hex(random_bytes(5));

        $this->files->ensureDirectoryExists($this->basePath.'/bootstrap');
        $this->files->ensureDirectoryExists($this->basePath.'/routes');

        $this->files->put($this->basePath.'/routes/web.php', '<?php');
        $this->files->put($this->basePath.'/routes/console.php', '<?php');

        $this->files->put($this->basePath.'/bootstrap/app.php', <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
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
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function testItConfiguresJsonExceptionRenderingForApiRoutes()
    {
        $command = $this->newCommand();

        $this->runCommand($command, ['--without-migration-prompt' => true]);

        $content = file_get_contents($this->basePath.'/bootstrap/app.php');

        $this->assertFileExists($this->basePath.'/routes/api.php');
        $this->assertStringContainsString("api: __DIR__.'/../routes/api.php',", $content);
        $this->assertStringContainsString('$exceptions->shouldRenderJsonForApiRoutes();', $content);
    }

    public function testItDoesNotDuplicateJsonExceptionConfigurationForApiRoutes()
    {
        $command = $this->newCommand();

        $this->runCommand($command, ['--without-migration-prompt' => true]);
        $this->runCommand($command, ['--without-migration-prompt' => true]);

        $content = file_get_contents($this->basePath.'/bootstrap/app.php');

        $this->assertSame(1, substr_count($content, '$exceptions->shouldRenderJsonForApiRoutes();'));
    }

    protected function newCommand(): ApiInstallCommand
    {
        $command = new class extends ApiInstallCommand
        {
            protected function installSanctum()
            {
                //
            }

            protected function installPassport()
            {
                //
            }
        };

        $command->setLaravel(new Application($this->basePath));

        return $command;
    }

    protected function runCommand(ApiInstallCommand $command, array $options = [])
    {
        return $command->run(new ArrayInput($options), new BufferedOutput);
    }
}
