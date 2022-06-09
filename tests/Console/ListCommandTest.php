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
        $output = $this->listConsole('', []);

        // Should see default Symfony "completion" command
        $this->assertMatchesRegularExpression("/Available commands:\n\s+completion/s", $output);
    }

    public function testDoesNotShowDefaultCommandsWithExceptVendor()
    {
        $output = $this->listConsole('xyz', ['--except-vendor' => true]);

        // Should not see default Symfony "completion" command
        $this->assertDoesNotMatchRegularExpression("/Available commands:\n\s+completion/s", $output);

        // Should see App namespace and ClosureCommands
        $this->assertMatchesRegularExpression("/Available commands:\n\s+example.*?\n\s+example-closure/s", $output);
    }

    public function testDoesNotShowClosureCommandsInsideVendorWithExceptVendor()
    {
        $output = $this->listConsole(__DIR__, ['--except-vendor' => true]);

        // Should not see example-closure command
        $this->assertDoesNotMatchRegularExpression("/Available commands:\n\s+example-closure/s", $output);
    }

    public function testDoesNotShowCustomCommandsWithOnlyVendor()
    {
        $output = $this->listConsole('xyz', ['--only-vendor' => true]);

        // Should see default Symfony "completion" command
        $this->assertMatchesRegularExpression("/Available commands:\n\s+completion/s", $output);

        // Should not see App namespace and ClosureCommands
        $this->assertDoesNotMatchRegularExpression("/Available commands:\n\s+example.*?\n\s+example-closure/s", $output);
    }

    public function testDoesShowClosureCommandsInsideVendorWithOnlyVendor()
    {
        $output = $this->listConsole(__DIR__, ['--only-vendor' => true]);

        // Should see example-closure command
        $this->assertMatchesRegularExpression("/Available commands:.*?\n\s+example-closure/s", $output);
    }

    private function listConsole(string $vendorDir, array $options)
    {
        $console = new Application(
            $app = m::mock(ApplicationContract::class, ['version' => '6.0']),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );
        $console->add(new ExampleCommand);
        $console->add(new ClosureCommand('example-closure', function () {}));

        $app->shouldReceive('getNamespace')->andReturn('App\\');
        /*
         * Returning __DIR__ as the $vendorDir here will make the application think
         * that the ConsoleCommand defined defined above is from the "vendor"
         * directory.
         */
        $app->shouldReceive('basePath')->with('vendor')->andReturn($vendorDir);
        $app->shouldReceive('make')->with(Dispatcher::class)->andReturn($events);

        $output = new BufferedOutput;
        $console->call('list', $options, $output);

        return $output->fetch();
    }
}

namespace App;

use Illuminate\Console\Command;

class ExampleCommand extends Command
{
    protected $signature = 'example';
}
