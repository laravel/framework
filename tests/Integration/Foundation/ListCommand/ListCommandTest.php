<?php

namespace Illuminate\Tests\Integration\Foundation\ListCommand;

use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application as FoundationApplication;
use Illuminate\Foundation\Console\ListCommand;
use Illuminate\Tests\Integration\Foundation\ListCommand\fixtures\app\AppCommand;
use Illuminate\Tests\Integration\Foundation\ListCommand\fixtures\vendor\VendorCommand;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ListCommandTest extends TestCase
{
    public function testVendorCommandsCanBeHidden()
    {
        $output = $this->runCommand(['--no-vendor' => true]);

        $this->assertStringContainsString('app-command', $output);
        $this->assertStringNotContainsString('vendor-command', $output);
    }

    public function testVendorCommandsAreVisibleByDefault()
    {
        $output = $this->runCommand([]);

        $this->assertStringContainsString('app-command', $output);
        $this->assertStringContainsString('vendor-command', $output);
    }

    protected function runCommand($parameters)
    {
        $output = new BufferedOutput();

        $app = new FoundationApplication();
        $app->setBasePath(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures');

        $artisan = new ConsoleApplication(
            $app,
            m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );
        $artisan->add(new AppCommand());
        $artisan->add(new VendorCommand());

        $command = new ListCommand();
        $command->setLaravel($app);
        $command->setApplication($artisan);
        $command->run(new ArrayInput($parameters), $output);

        return $output->fetch();
    }
}
