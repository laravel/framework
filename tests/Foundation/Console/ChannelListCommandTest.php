<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\Application;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application as FoundationApplication;
use Illuminate\Foundation\Console\ChannelListCommand;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ChannelListCommandTest extends TestCase
{
    public function testItDisplaysAnErrorWhenThereAreNoChannels(): void
    {
        $app = $this->makeApplication([]);

        $app->call('channel:list');

        $this->assertStringContainsString(
            "doesn't have any private broadcasting channels", $app->output()
        );
    }

    public function testItListsRegisteredChannels(): void
    {
        $app = $this->makeApplication([
            'orders.{order}' => fn () => true,
        ]);

        $app->call('channel:list');

        $output = $app->output();

        $this->assertStringContainsString('orders.{order}', $output);
        $this->assertStringContainsString('Showing [1] private channels', $output);
    }

    protected function makeApplication(array $channels): Application
    {
        $laravel = new FoundationApplication(__DIR__);

        $broadcaster = m::mock(BroadcasterContract::class);
        $broadcaster->expects('getChannels')->andReturn(new Collection($channels));

        $laravel->instance(BroadcasterContract::class, $broadcaster);

        $artisan = new Application(
            $laravel,
            m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $command = new ChannelListCommand;
        $command->setLaravel($laravel);

        $artisan->addCommands([$command]);

        return $artisan;
    }
}
