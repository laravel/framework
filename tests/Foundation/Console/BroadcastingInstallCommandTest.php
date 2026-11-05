<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\BroadcastingInstallCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class BroadcastingInstallCommandTest extends TestCase
{
    protected $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir().'/laravel-broadcasting-install-'.uniqid();

        (new Filesystem)->ensureDirectoryExists($this->basePath.'/resources/js');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->basePath);

        Application::setInstance(null);
    }

    #[DataProvider('frameworkFiles')]
    public function testEchoConfigurationIsInjectedIntoTheApplicationFile($package, $file)
    {
        file_put_contents($this->basePath.'/package.json', json_encode(['devDependencies' => [$package => '*']]));
        file_put_contents($this->basePath.'/resources/js/'.$file, "import './bootstrap';\n");

        $command = new BroadcastingInstallCommandTestStub;
        $command->setLaravel(new Application($this->basePath));
        $command->injectEchoConfiguration('reverb');

        $this->assertStringContainsString(
            "import { configureEcho } from '@laravel/echo-{$package}';",
            file_get_contents($this->basePath.'/resources/js/'.$file)
        );
    }

    public static function frameworkFiles()
    {
        return [
            ['react', 'app.tsx'],
            ['react', 'app.jsx'],
            ['vue', 'app.ts'],
            ['vue', 'app.js'],
        ];
    }
}

class BroadcastingInstallCommandTestStub extends BroadcastingInstallCommand
{
    public function injectEchoConfiguration($driver)
    {
        $this->driver = $driver;
        $this->components = new Factory(new OutputStyle(new ArrayInput([]), new BufferedOutput));

        $this->injectFrameworkSpecificConfiguration();
    }
}
