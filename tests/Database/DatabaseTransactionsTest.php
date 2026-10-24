<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\DatabaseTransactionsManager;
use Mockery;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
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

    public function testNestedTransactionPreservesOriginalExceptionAndResetsLevelWhenSavepointRollbackFails()
    {
        $caught = null;

        try {
            $this->connection()->transaction(function () {
                $this->connection()->table('users')->insert(['name' => 'first', 'value' => 1]);

                $this->connection()->transaction(function () {
                    $this->connection()->table('users')->insert(['name' => 'second', 'value' => 2]);
                    $this->connection()->unprepared('RELEASE SAVEPOINT trans2');

                    throw new RuntimeException('Original nested query failure');
                });
            });
        } catch (Throwable $e) {
            $caught = $e;
        }

        $this->assertInstanceOf(RuntimeException::class, $caught);
        $this->assertSame('Original nested query failure', $caught->getMessage());
        $this->assertInstanceOf(PDOException::class, $caught->getPrevious());
        $this->assertStringContainsString('trans2', $caught->getPrevious()->getMessage());
        $this->assertSame(0, $this->connection()->transactionLevel());
        $this->assertCount(0, $this->connection()->table('users')->get());
    }

    public function testSubsequentTransactionCanCommitAfterFailedSavepointRollback()
    {
        try {
            $this->connection()->transaction(function () {
                $this->connection()->table('users')->insert(['name' => 'first', 'value' => 1]);

                $this->connection()->transaction(function () {
                    $this->connection()->table('users')->insert(['name' => 'second', 'value' => 2]);
                    $this->connection()->unprepared('RELEASE SAVEPOINT trans2');

                    throw new RuntimeException('Original nested query failure');
                });
            });
        } catch (Throwable) {
        }

        $this->assertSame(0, $this->connection()->transactionLevel());

        $this->connection()->transaction(function () {
            $this->connection()->table('users')->insert(['name' => 'persisted', 'value' => 10]);
        });

        $this->assertSame(0, $this->connection()->transactionLevel());
        $this->assertCount(1, $this->connection()->table('users')->get());
        $this->assertSame('persisted', $this->connection()->table('users')->first()->name);
    }

    public function testOuterTransactionCanRecoverAndCommitWhenNestedSavepointRollbackFails()
    {
        $caughtInner = null;

        $this->connection()->transaction(function () use (&$caughtInner) {
            $this->connection()->table('users')->insert(['name' => 'first', 'value' => 1]);

            try {
                $this->connection()->transaction(function () {
                    $this->connection()->table('users')->insert(['name' => 'second', 'value' => 2]);
                    $this->connection()->unprepared('RELEASE SAVEPOINT trans2');

                    throw new RuntimeException('Inner failure');
                });
            } catch (Throwable $e) {
                $caughtInner = $e;
            }

            $this->assertSame(1, $this->connection()->transactionLevel());
            $this->connection()->table('users')->insert(['name' => 'outer_committed', 'value' => 100]);
        });

        $this->assertSame(0, $this->connection()->transactionLevel());
        $this->assertInstanceOf(RuntimeException::class, $caughtInner);
        $this->assertSame('Inner failure', $caughtInner->getMessage());
        $this->assertInstanceOf(PDOException::class, $caughtInner->getPrevious());
        $this->assertCount(3, $this->connection()->table('users')->get());
    }

    public function testManualRollbackResetsLevelWhenSavepointRollbackFails()
    {
        $this->connection()->beginTransaction();
        $this->connection()->beginTransaction();
        $this->assertSame(2, $this->connection()->transactionLevel());

        $this->connection()->unprepared('RELEASE SAVEPOINT trans2');

        $caught = null;
        try {
            $this->connection()->rollBack();
        } catch (Throwable $e) {
            $caught = $e;
        }

        $this->assertInstanceOf(PDOException::class, $caught);
        $this->assertSame(1, $this->connection()->transactionLevel());

        $this->connection()->rollBack();
        $this->assertSame(0, $this->connection()->transactionLevel());
    }

    public function testNestedTransactionNotifiesTransactionManagerWhenSavepointRollbackFails()
    {
        $transactionManager = Mockery::mock(new DatabaseTransactionsManager);
        $transactionManager->expects('begin')->with('default', 1);
        $transactionManager->expects('begin')->with('default', 2);
        $transactionManager->expects('rollback')->with('default', 1);
        $transactionManager->expects('rollback')->with('default', 0);
        $transactionManager->shouldNotReceive('commit');

        $this->connection()->setTransactionManager($transactionManager);

        try {
            $this->connection()->transaction(function () {
                $this->connection()->transaction(function () {
                    $this->connection()->unprepared('RELEASE SAVEPOINT trans2');

                    throw new RuntimeException('Inner failure');
                });
            });
        } catch (Throwable) {
        }

        $this->assertSame(0, $this->connection()->transactionLevel());
    }

    public function testExistingPreviousExceptionPreservedWhenChainingRollbackException()
    {
        $caught = null;
        $rootCause = new Exception('Root cause');

        try {
            $this->connection()->transaction(function () use ($rootCause) {
                $this->connection()->transaction(function () use ($rootCause) {
                    $this->connection()->unprepared('RELEASE SAVEPOINT trans2');

                    throw new RuntimeException('Outer error', 0, $rootCause);
                });
            });
        } catch (Throwable $e) {
            $caught = $e;
        }

        $this->assertInstanceOf(RuntimeException::class, $caught);
        $this->assertSame('Outer error', $caught->getMessage());
        $this->assertSame($rootCause, $caught->getPrevious());
        $this->assertInstanceOf(PDOException::class, $caught->getPrevious()->getPrevious());
        $this->assertSame(0, $this->connection()->transactionLevel());
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
