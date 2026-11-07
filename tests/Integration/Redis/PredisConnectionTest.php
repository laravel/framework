<?php

namespace Illuminate\Tests\Integration\Redis;

use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use Predis\Client;
use Predis\Command\Argument\Search\SearchArguments;

#[WithConfig('database.redis.client', 'predis')]
class PredisConnectionTest extends TestCase
{
    public function testPredisCanEmitEventWithArrayableArgumentObject()
    {
        if (! class_exists(SearchArguments::class)) {
            return $this->markTestSkipped('Skipped tests on predis/predis dependency without '.SearchArguments::class);
        }

        $event = Event::fake();

        $command = 'ftSearch';
        $parameters = ['test', '*', (new SearchArguments())->dialect('3')->withScores()];

        $client = new class extends Client
        {
            public array $received = [];

            public function ftSearch(...$arguments)
            {
                $this->received = $arguments;

                return true;
            }
        };
        $predis = new PredisConnection($client);
        $predis->setEventDispatcher($event);

        $this->assertTrue($predis->command($command, $parameters));
        $this->assertEquals($parameters, $client->received);

        $event->assertDispatched(function (CommandExecuted $event) use ($command) {
            return $event->connection instanceof PredisConnection
                && $event->command === $command
                && $event->parameters === ['test', '*', ['DIALECT', '3', 'WITHSCORES']];
        });
    }
}
