<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Events\Dispatcher;
use Mockery;
use PDOException;
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
    }

    public function testTransactionIsRecordedAndCommitted()
    {
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('commit')->with('default', 1, 0);

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
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('commit')->with('default', 1, 0);

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
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('begin')->with('default', 2);
        $transactionManager->expects('commit')->with('default', 2, 1);
        $transactionManager->expects('commit')->with('default', 1, 0);

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
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('begin')->with('second_connection', 1);
        $transactionManager->expects('begin')->with('second_connection', 2);
        $transactionManager->expects('commit')->with('default', 1, 0);
        $transactionManager->expects('commit')->with('second_connection', 2, 1);
        $transactionManager->expects('commit')->with('second_connection', 1, 0);

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
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('rollback')->with('default', 0);
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
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('rollback')->with('default', 0);
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
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('begin')->with('default', 2);
        $transactionManager->expects('rollback')->with('default', 1);
        $transactionManager->expects('rollback')->with('default', 0);
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

    public function testTransactionManagerIsRolledBackWhenCommitFailsWithConcurrencyErrorAndRetries()
    {
        // Use a real manager so rollback() actually discards failed-attempt callbacks.
        $this->connection()->setTransactionManager(new DatabaseTransactionsManager);
        $this->connection()->setEventDispatcher($dispatcher = new Dispatcher);

        $failOnce = true;
        $dispatcher->listen(TransactionCommitting::class, function () use (&$failOnce) {
            if ($failOnce) {
                $failOnce = false;

                throw new PDOException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock');
            }
        });

        $attempt = 0;
        $ran = [];

        $this->connection()->transaction(function () use (&$attempt, &$ran) {
            $n = ++$attempt;
            $this->connection()->afterCommit(function () use (&$ran, $n) {
                $ran[] = "attempt {$n}";
            });

            $this->connection()->table('users')->insert([
                'name' => "user-{$n}", 'value' => $n,
            ]);
        }, 2);

        $this->assertSame(['attempt 2'], $ran);
        $this->assertSame(0, $this->connection()->transactionLevel());
    }

    public function testTransactionManagerIsRolledBackWhenCommitFailsWithoutRetry()
    {
        $this->connection()->setTransactionManager(new DatabaseTransactionsManager);
        $this->connection()->setEventDispatcher($dispatcher = new Dispatcher);

        $dispatcher->listen(TransactionCommitting::class, function () {
            throw new Exception('Commit failed');
        });

        $outsideRan = false;

        try {
            $this->connection()->transaction(function () {
                $this->connection()->table('users')->insert([
                    'name' => 'zain', 'value' => 1,
                ]);
            });
            $this->fail('Expected commit failure to throw.');
        } catch (Exception $e) {
            $this->assertSame('Commit failed', $e->getMessage());
        }

        $this->assertSame(0, $this->connection()->transactionLevel());

        // afterCommit outside any transaction should run immediately once the
        // manager no longer holds the failed transaction as pending.
        $this->connection()->afterCommit(function () use (&$outsideRan) {
            $outsideRan = true;
        });

        $this->assertTrue($outsideRan);
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
