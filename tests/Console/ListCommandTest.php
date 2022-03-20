<?php

namespace Illuminate\Tests\Console;

use App\ExampleCommand;
use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\ListCommand;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Output\BufferedOutput;

class ListCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testShowsAllCommandsByDefault()
    {
        $app = new Application(
            $app = m::mock(ApplicationContract::class, ['version' => '6.0']),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $output = new BufferedOutput();
        $app->call('list', [], $output);

        // Should see default Symfony "completion" command
        $this->assertMatchesRegularExpression("/Available commands:\n\s+completion/s", $output->fetch());
    }

    public function testDoesNotShowDefaultCommandsWithExceptVendor()
    {
        $console = new Application(
            $app = m::mock(ApplicationContract::class, ['version' => '6.0']),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );
        $console->add(new ExampleCommand);
        $console->add(new ClosureCommand('example-closure', function () {}));

        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $app->shouldReceive('basePath')->with('vendor')->andReturn('/xyz');
        $app->shouldReceive('make')->with(Dispatcher::class)->andReturn($events);

        $output = new BufferedOutput();
        $console->call('list', ['--except-vendor' => true], $output);

        $outputStr = $output->fetch();

        // Should not see default Symfony "completion" command
        $this->assertDoesNotMatchRegularExpression("/Available commands:\n\s+completion/s", $outputStr);

        // Should see App namespace and ClosureCommands
        $this->assertMatchesRegularExpression("/Available commands:\n\s+example.*?\n\s+example-closure/s", $outputStr);
    }

    public function testDoesNotShowClosureCommandsInsideVendorWithExceptVendor()
    {
        $console = new Application(
            $app = m::mock(ApplicationContract::class, ['version' => '6.0']),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );
        $console->add(new ExampleCommand);
        $console->add(new ClosureCommand('example-closure', function () {}));

        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $app->shouldReceive('basePath')->with('vendor')->andReturn(__DIR__);
        $app->shouldReceive('make')->with(Dispatcher::class)->andReturn($events);

        $output = new BufferedOutput();
        $console->call('list', ['--except-vendor' => true], $output);

        $outputStr = $output->fetch();

        // Should not see example-closure command
        $this->assertDoesNotMatchRegularExpression("/Available commands:\n\s+example-closure/s", $outputStr);
    }
}

namespace App;

use Illuminate\Console\Command;

class ExampleCommand extends Command
{
    protected $signature = 'example';
}
