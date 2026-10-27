<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\SQLiteConnection;
use Mockery;
use PDO;
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

    public function testFailedCommitRollsBackTheTransactionsManager()
    {
        $manager = new DatabaseTransactionsManager;
        $this->connection()->setTransactionManager($manager);

        $afterCommitRan = false;
        $rolledBack = false;

        try {
            $this->connection()->transaction(function () use (&$afterCommitRan, &$rolledBack) {
                $this->connection()->afterCommit(function () use (&$afterCommitRan) {
                    $afterCommitRan = true;
                });

                $this->connection()->afterRollBack(function () use (&$rolledBack) {
                    $rolledBack = true;
                });

                // End the transaction behind the connection's back so COMMIT fails.
                $this->connection()->getPdo()->exec('ROLLBACK');
            });

            $this->fail('The failed commit should have thrown.');
        } catch (PDOException) {
        }

        $this->assertSame(0, $this->connection()->transactionLevel());
        $this->assertCount(0, $manager->getPendingTransactions());
        $this->assertFalse($afterCommitRan);
        $this->assertTrue($rolledBack);
    }

    public function testNestedDeadlockRollsBackTheTransactionsManagerToTheOuterLevel()
    {
        $manager = new DatabaseTransactionsManager;
        $this->connection()->setTransactionManager($manager);

        $innerRolledBack = false;

        $this->connection()->transaction(function () use ($manager, &$innerRolledBack) {
            try {
                $this->connection()->transaction(function () use (&$innerRolledBack) {
                    $this->connection()->afterRollBack(function () use (&$innerRolledBack) {
                        $innerRolledBack = true;
                    });

                    throw new PDOException('Deadlock found when trying to get lock');
                });

                $this->fail('The deadlock should have thrown.');
            } catch (DeadlockException) {
            }

            $this->assertSame(1, $this->connection()->transactionLevel());
            $this->assertCount(1, $manager->getPendingTransactions());
            $this->assertTrue($innerRolledBack);
        });

        $this->assertSame(0, $this->connection()->transactionLevel());
        $this->assertCount(0, $manager->getPendingTransactions());
    }

    public function testRollBackOnLostConnectionClearsTheTransactionsManager()
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('beginTransaction')->andReturnTrue();
        $pdo->allows('exec')->andReturn(0);
        $pdo->expects('inTransaction')->andReturnTrue();
        $pdo->expects('rollBack')->andThrow(new PDOException('server has gone away'));

        $manager = new DatabaseTransactionsManager;
        $connection = (new SQLiteConnection($pdo, config: ['name' => 'lost']))->setTransactionManager($manager);

        $rolledBack = false;

        $connection->beginTransaction();
        $connection->afterRollBack(function () use (&$rolledBack) {
            $rolledBack = true;
        });

        try {
            $connection->rollBack();

            $this->fail('The lost connection should have thrown.');
        } catch (PDOException $e) {
            $this->assertSame('server has gone away', $e->getMessage());
        }

        $this->assertSame(0, $connection->transactionLevel());
        $this->assertCount(0, $manager->getPendingTransactions());
        $this->assertTrue($rolledBack);
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
