<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Sleep;
use Mockery as m;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

class DatabaseTransactionsTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'second_connection');

        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        foreach (['default', 'second_connection'] as $connection) {
            $this->schema($connection)->create('users', function ($table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('value')->nullable();
            });
        }
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (['default', 'second_connection'] as $connection) {
            $this->schema($connection)->drop('users');
        }

        m::close();
    }

    public function testTransactionIsRecordedAndCommitted()
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->once()->with('default', 1);
        $transactionManager->shouldReceive('commit')->once()->with('default', 1, 0);

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        $this->connection()->transaction(function () {
            $this->connection()->table('users')->where(['name' => 'zain'])->update([
                'value' => 2,
            ]);
        });
    }

    public function testTransactionIsRecordedAndCommittedUsingTheSeparateMethods()
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->once()->with('default', 1);
        $transactionManager->shouldReceive('commit')->once()->with('default', 1, 0);

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        $this->connection()->beginTransaction();
        $this->connection()->table('users')->where(['name' => 'zain'])->update([
            'value' => 2,
        ]);
        $this->connection()->commit();
    }

    public function testNestedTransactionIsRecordedAndCommitted()
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->once()->with('default', 1);
        $transactionManager->shouldReceive('begin')->once()->with('default', 2);
        $transactionManager->shouldReceive('commit')->once()->with('default', 2, 1);
        $transactionManager->shouldReceive('commit')->once()->with('default', 1, 0);

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        $this->connection()->transaction(function () {
            $this->connection()->table('users')->where(['name' => 'zain'])->update([
                'value' => 2,
            ]);

            $this->connection()->transaction(function () {
                $this->connection()->table('users')->where(['name' => 'zain'])->update([
                    'value' => 2,
                ]);
            });
        });
    }

    public function testNestedTransactionIsRecordeForDifferentConnectionsdAndCommitted()
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->once()->with('default', 1);
        $transactionManager->shouldReceive('begin')->once()->with('second_connection', 1);
        $transactionManager->shouldReceive('begin')->once()->with('second_connection', 2);
        $transactionManager->shouldReceive('commit')->once()->with('default', 1, 0);
        $transactionManager->shouldReceive('commit')->once()->with('second_connection', 2, 1);
        $transactionManager->shouldReceive('commit')->once()->with('second_connection', 1, 0);

        $this->connection()->setTransactionManager($transactionManager);
        $this->connection('second_connection')->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        $this->connection()->transaction(function () {
            $this->connection()->table('users')->where(['name' => 'zain'])->update([
                'value' => 2,
            ]);

            $this->connection('second_connection')->transaction(function () {
                $this->connection('second_connection')->table('users')->where(['name' => 'zain'])->update([
                    'value' => 2,
                ]);

                $this->connection('second_connection')->transaction(function () {
                    $this->connection('second_connection')->table('users')->where(['name' => 'zain'])->update([
                        'value' => 2,
                    ]);
                });
            });
        });
    }

    public function testTransactionIsRolledBack()
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->once()->with('default', 1);
        $transactionManager->shouldReceive('rollback')->once()->with('default', 0);
        $transactionManager->shouldNotReceive('commit');

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        try {
            $this->connection()->transaction(function () {
                $this->connection()->table('users')->where(['name' => 'zain'])->update([
                    'value' => 2,
                ]);

                throw new Exception;
            });
        } catch (Throwable) {
        }
    }

    public function testTransactionIsRolledBackUsingSeparateMethods()
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->once()->with('default', 1);
        $transactionManager->shouldReceive('rollback')->once()->with('default', 0);
        $transactionManager->shouldNotReceive('commit', 1, 0);

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        $this->connection()->beginTransaction();

        $this->connection()->table('users')->where(['name' => 'zain'])->update([
            'value' => 2,
        ]);

        $this->connection()->rollBack();
    }

    public function testNestedTransactionsAreRolledBack()
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->once()->with('default', 1);
        $transactionManager->shouldReceive('begin')->once()->with('default', 2);
        $transactionManager->shouldReceive('rollback')->once()->with('default', 1);
        $transactionManager->shouldReceive('rollback')->once()->with('default', 0);
        $transactionManager->shouldNotReceive('commit');

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        try {
            $this->connection()->transaction(function () {
                $this->connection()->table('users')->where(['name' => 'zain'])->update([
                    'value' => 2,
                ]);

                $this->connection()->transaction(function () {
                    $this->connection()->table('users')->where(['name' => 'zain'])->update([
                        'value' => 2,
                    ]);

                    throw new Exception;
                });
            });
        } catch (Throwable) {
        }
    }

    #[DataProvider('backOffProvider')]
    public function testTransactionIsRolledBackAndBackedOff($backoff, $expectedSleepSequence)
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->times(4)->with('default', 1);
        $transactionManager->shouldReceive('rollback')->times(4)->with('default', 0);
        $transactionManager->shouldNotReceive('commit');

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        Sleep::fake();

        try {
            $this->connection()->transaction(
                callback: function () {
                    $this->connection()->table('users')->where(['name' => 'zain'])->update([
                        'value' => 2,
                    ]);

                    throw new PDOException('deadlock detected', 40001);
                },
                attempts: 4,
                backoff: $backoff,
            );
        } catch (PDOException) {
        }

        if (count($expectedSleepSequence) > 0) {
            Sleep::assertSequence($expectedSleepSequence);
        } else {
            Sleep::assertNeverSlept();
        }
    }

    #[DataProvider('backOffProvider')]
    public function testNestedTransactionsAreRolledBackAndBackedOff($backoff, $expectedSleepSequence)
    {
        $transactionManager = m::mock(new DatabaseTransactionsManager);
        $transactionManager->shouldReceive('begin')->times(4)->with('default', 1);
        $transactionManager->shouldReceive('begin')->times(4)->with('default', 2);
        $transactionManager->shouldReceive('rollback')->times(4)->with('default', 1);
        $transactionManager->shouldReceive('rollback')->times(4)->with('default', 0);
        $transactionManager->shouldNotReceive('commit');

        $this->connection()->setTransactionManager($transactionManager);

        $this->connection()->table('users')->insert([
            'name' => 'zain', 'value' => 1,
        ]);

        Sleep::fake();

        try {
            $this->connection()->transaction(
                callback: function () {
                    $this->connection()->table('users')->where(['name' => 'zain'])->update([
                        'value' => 2,
                    ]);

                    $this->connection()->transaction(function () {
                        $this->connection()->table('users')->where(['name' => 'zain'])->update([
                            'value' => 3,
                        ]);

                        throw new PDOException('deadlock detected', 40001);
                    });
                },
                attempts: 4,
                backoff: $backoff,
            );
        } catch (PDOException) {
        }

        if (count($expectedSleepSequence) > 0) {
            Sleep::assertSequence($expectedSleepSequence);
        } else {
            Sleep::assertNeverSlept();
        }
    }

    public static function backOffProvider()
    {
        yield 'null backoff' => [
            null,
            [],
        ];

        yield 'integer backoff' => [
            42,
            [
                Sleep::for(42)->milliseconds(),
                Sleep::for(42)->milliseconds(),
                Sleep::for(42)->milliseconds(),
            ],
        ];

        yield 'array backoff' => [
            [1111, 2222],
            [
                Sleep::for(1111)->milliseconds(),
                Sleep::for(2222)->milliseconds(),
                Sleep::for(2222)->milliseconds(),
            ],
        ];

        yield 'collection backoff' => [
            collect([1111, 2222]),
            [
                Sleep::for(1111)->milliseconds(),
                Sleep::for(2222)->milliseconds(),
                Sleep::for(2222)->milliseconds(),
            ],
        ];

        yield 'callable backoff' => [
            fn (Throwable $e, int $currentAttempt, int $maxAttempts): int => $currentAttempt * 2222,
            [
                Sleep::for(2222)->milliseconds(),
                Sleep::for(4444)->milliseconds(),
                Sleep::for(6666)->milliseconds(),
            ],
        ];
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }

    public function connection($name = 'default')
    {
        return DB::connection($name);
    }
}
