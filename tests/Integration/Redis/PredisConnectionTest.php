<?php

namespace Illuminate\Tests\Integration\Redis;

use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use Predis\Client;
use Predis\Command\Argument\Search\SearchArguments;

class PredisConnectionTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app->get('config')->set('database.redis.client', 'predis');
    }

    public function testPredisCanEmitEventWithArrayableArgumentObject()
    {
        if (! class_exists(SearchArguments::class)) {
            return $this->markTestSkipped('Skipped tests on predis/predis dependency without '.SearchArguments::class);
        }

        $event = Event::fake();

        $command = 'ftSearch';
        $parameters = ['test', '*', (new SearchArguments())->dialect('3')->withScores()];

        $predis = new PredisConnection($client = m::mock(Client::class));
        $predis->setEventDispatcher($event);

        $client->shouldReceive($command)->with(...$parameters)->andReturnTrue();

        $this->assertTrue($predis->command($command, $parameters));

        $event->assertDispatched(function (CommandExecuted $event) use ($command) {
            return $event->connection instanceof PredisConnection
                && $event->command === $command
                && $event->parameters === ['test', '*', ['DIALECT', '3', 'WITHSCORES']];
        });
    }
}
