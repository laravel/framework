<?php

namespace Illuminate\Tests\Pipeline;

use Exception;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Pipeline;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PipelineTransactionTest extends TestCase
{
    public function testPipelineTransaction()
    {
        Event::fake();

        $result = Pipeline::withinTransaction()
            ->send('some string')
            ->through([
                fn ($value, $next) => $next($value),
                fn ($value, $next) => $next($value),
            ])
            ->thenReturn();

        $this->assertEquals('some string', $result);
        Event::assertDispatchedTimes(TransactionBeginning::class, 1);
        Event::assertDispatchedTimes(TransactionCommitted::class, 1);
    }

    public static function transactionConnectionDataProvider(): array
    {
        return [
            'unit enum' => [EnumForPipelineTransactionTest::DEFAULT, 'testing'],
            'string' => ['testing', 'testing'],
            'null' => [null, 'testing2'],
        ];
    }

    #[DataProvider('transactionConnectionDataProvider')]
    public function testConnection($connection, $connectionName)
    {
        Event::fake();
        config(['database.connections.testing2' => config('database.connections.testing')]);
        config(['database.default' => 'testing2']);

        $result = Pipeline::withinTransaction($connection)
            ->send('some string')
            ->through([
                function ($value, $next) {
                    return $next($value);
                },
            ])
            ->thenReturn();

        $this->assertEquals('some string', $result);
        Event::dispatched(TransactionBeginning::class, function (TransactionBeginning $event) use ($connectionName) {
            return $event->connection === $connectionName;
        });
    }

    public function testExceptionThrownRollsBackTransaction()
    {
        Event::fake();

        $finallyRan = false;
        try {
            Pipeline::withinTransaction()
                ->send('some string')
                ->through([
                    function ($value, $next) {
                        throw new Exception('I was thrown');
                    },
                ])
                ->finally(function () use (&$finallyRan) {
                    $finallyRan = true;
                })
                ->thenReturn();
            $this->fail('No exception was thrown');
        } catch (Exception) {
        }

        $this->assertTrue($finallyRan);
        Event::assertDispatched(TransactionBeginning::class);
        Event::assertDispatched(TransactionRolledBack::class);
    }
}

enum EnumForPipelineTransactionTest: string
{
    case DEFAULT = 'testing';
}
