<?php

namespace Illuminate\Tests\Pipeline;

use Exception;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Pipeline;
use Orchestra\Testbench\TestCase;

class PipelineTransactionTest extends TestCase
{
    public function testPipelineTransaction()
    {
        Event::fake();

        $result = Pipeline::inTransaction()
            ->send('some string')
            ->through([function ($value, $next) {
                return $next($value);
            }])
            ->thenReturn();

        $this->assertEquals('some string', $result);
        Event::assertDispatched(TransactionBeginning::class);
        Event::assertDispatched(TransactionCommitted::class);
    }

    public function testExceptionThrownRollsBackTransaction()
    {
        Event::fake();

        $finallyRan = false;
        try {
            Pipeline::inTransaction()
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
