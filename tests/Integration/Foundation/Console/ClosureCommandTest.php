<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Orchestra\Testbench\TestCase;

class ClosureCommandTest extends TestCase
{
    /** {@inheritDoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        Artisan::command('inspire', function () {
            $this->comment('We must ship. - Taylor Otwell');
        })->purpose('Display an inspiring quote')->metadata([
            'operations' => [
                'owner' => 'framework',
            ],
        ]);
    }

    public function testItCanRunClosureCommand()
    {
        $this->artisan('inspire')->expectsOutput('We must ship. - Taylor Otwell');
    }

    public function testClosureCommandMetadataRemainsInspectableAfterRegistration()
    {
        $command = Artisan::all()['inspire'];

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('framework', $command->getMetadata('operations.owner'));
    }

    public function testClosureCommandSupportsMetadataWithoutCreatingAScheduledEvent()
    {
        $command = new ClosureCommand('metadata:test', fn () => null);

        $this->assertSame($command, $command->metadata(['owner' => 'finance']));
        $this->assertSame(['owner' => 'finance'], $command->getMetadata());
        $this->assertSame([], Schedule::events());
    }

    public function testConcreteMetadataMethodTakesPrecedenceOverScheduledEventMacro()
    {
        $macroCalled = false;

        Event::macro('metadata', function () use (&$macroCalled) {
            $macroCalled = true;

            return $this;
        });

        try {
            $command = (new ClosureCommand('metadata:test', fn () => null))
                ->metadata(['owner' => 'finance']);

            $this->assertFalse($macroCalled);
            $this->assertSame(['owner' => 'finance'], $command->getMetadata());
            $this->assertSame([], Schedule::events());
        } finally {
            Event::flushMacros();
        }
    }

    public function testClosureCommandContinuesToForwardSchedulingMethods()
    {
        $event = (new ClosureCommand('metadata:test', fn () => null))->daily();

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('0 0 * * *', $event->expression);
    }
}
