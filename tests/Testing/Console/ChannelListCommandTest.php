<?php

namespace Illuminate\Tests\Testing\Console;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Console\ChannelListCommand;
use Orchestra\Testbench\TestCase;

class ChannelListCommandTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\Broadcaster
     */
    private $broadcaster;

    protected function setUp(): void
    {
        parent::setUp();

        $this->broadcaster = $this->app->make(Broadcaster::class);

        ChannelListCommand::resolveTerminalWidthUsing(function () {
            return 90;
        });
    }

    public function testDisplayBroadcastChannels()
    {
        $this->broadcaster->channel('user', function () {
            //
        });

        $this->broadcaster->channel('Auth.Models.User.{id}', function () {
            //
        });

        $this->broadcaster->channel('chats.{chat}', function () {
            //
        });

        $this->broadcaster->channel('orders.{order}', FakeChannelClass::class);

        $this->artisan(ChannelListCommand::class)
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  Auth.Models.User.{id}       Closure ..................................................')
            ->expectsOutput('  chats.{chat}                Closure ..................................................')
            ->expectsOutput('  orders.{order}              Illuminate\Tests\Testing\Console\FakeChannelClass ........')
            ->expectsOutput('  user                        Closure ..................................................')
            ->expectsOutput('')
            ->expectsOutput('                                                            Showing [4] private channels')
            ->expectsOutput('');
    }

    public function testErrorMessageIsShownIfNoChannelsAreRegistered()
    {
        $this->artisan(ChannelListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain("Your application doesn't have any private broadcasting channels.");
    }
}

class FakeChannelClass
{
    public function join(User $user, Order $order): array|bool
    {
        return true;
    }
}

class Order extends Model
{
    //
}
