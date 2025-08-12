<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\SavepointCreated;
use Illuminate\Database\Events\SavepointReleased;
use Illuminate\Database\Events\SavepointRolledBack;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use LogicException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class DatabaseSavepointsTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function test_savepoint_creation(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();

        $connection->savepoint('savepoint');

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint').'"', $connection->getPdo()->executed);
        $this->assertTrue($connection->hasSavepoint('savepoint'));
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_creation_with_callback(): void
    {
        $connection = $this->connection(
            ['transactionLevel'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
            }
        );

        $result = $connection->savepoint('savepoint', static fn (): string => 'callback_invoked');

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint').'"', $connection->getPdo()->executed);
        $this->assertEquals('callback_invoked', $result);
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_callback_success_releases_savepoint(): void
    {
        $connection = $this->connection(
            ['transactionLevel', 'supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
                $connection->method('supportsSavepointRelease')->willReturn(true);
            }
        );

        $result = $connection->savepoint('savepoint', static fn (): string => 'callback_invoked');

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint').'"', $connection->getPdo()->executed);
        $this->assertContains('RELEASE SAVEPOINT "'.bin2hex('savepoint').'"', $connection->getPdo()->executed);
        $this->assertEmpty($connection->savepoints(1) ?? []);
        $this->assertEquals('callback_invoked', $result);
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_callback_failure_throws_exception(): void
    {
        $connection = $this->connection(
            ['transactionLevel'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
            }
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('callback_failed');

        $connection->savepoint(
            'savepoint',
            static function (): never {
                throw new RuntimeException('callback_failed');
            }
        );
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_callback_failure_performs_rollback(): void
    {
        $connection = $this->connection(
            ['transactionLevel'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
            }
        );

        try {
            $connection->savepoint('savepoint', static function (): never {
                throw new RuntimeException('callback_failed');
            });
        } catch (Exception) {
            // expected
        }

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint').'"', $connection->getPdo()->executed);
        $this->assertContains('ROLLBACK TO SAVEPOINT "'.bin2hex('savepoint').'"', $connection->getPdo()->executed);
    }

    /**
     * @throws Throwable
     */
    public function test_rollback_to_savepoint(): void
    {
        $connection = $this->connection(
            ['transactionLevel'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
            }
        );

        $connection->savepoint('savepoint1');
        $connection->savepoint('savepoint2');
        $connection->rollbackToSavepoint('savepoint1');

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint1').'"', $connection->getPdo()->executed);
        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint2').'"', $connection->getPdo()->executed);
        $this->assertContains('ROLLBACK TO SAVEPOINT "'.bin2hex('savepoint1').'"', $connection->getPdo()->executed);
        $this->assertEquals(['savepoint1'], $connection->savepoints(1) ?? []);
    }

    /**
     * @throws Throwable
     */
    public function test_release_savepoint(): void
    {
        $connection = $this->connection(
            ['transactionLevel', 'supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
                $connection->method('supportsSavepointRelease')->willReturn(true);
            }
        );

        $connection->savepoint('savepoint1');
        $connection->savepoint('savepoint2');
        $connection->releaseSavepoint('savepoint1');

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint1').'"', $connection->getPdo()->executed);
        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint2').'"', $connection->getPdo()->executed);
        $this->assertContains('RELEASE SAVEPOINT "'.bin2hex('savepoint1').'"', $connection->getPdo()->executed);
        $this->assertEquals(['savepoint2'], $connection->savepoints(1) ?? []);
    }

    /**
     * @throws Throwable
     */
    public function test_purge_savepoints(): void
    {
        $connection = $this->connection(
            ['transactionLevel', 'supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
                $connection->method('supportsSavepointRelease')->willReturn(true);
            }
        );

        $connection->savepoint('savepoint1');
        $connection->savepoint('savepoint2');
        $connection->savepoint('savepoint3');
        $connection->purgeSavepoints();

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint1').'"', $connection->getPdo()->executed);
        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint2').'"', $connection->getPdo()->executed);
        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint3').'"', $connection->getPdo()->executed);
        $this->assertContains('RELEASE SAVEPOINT "'.bin2hex('savepoint1').'"', $connection->getPdo()->executed);
        $this->assertContains('RELEASE SAVEPOINT "'.bin2hex('savepoint2').'"', $connection->getPdo()->executed);
        $this->assertContains('RELEASE SAVEPOINT "'.bin2hex('savepoint3').'"', $connection->getPdo()->executed);
        $this->assertEmpty($connection->savepoints(1) ?? []);
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_creation_throws_exception_when_savepoints_unsupported(): void
    {
        $connection = $this->connection(
            ['supportsSavepoints'],
            static function ($connection): void {
                $connection->method('supportsSavepoints')->willReturn(false);
            });

        $connection->beginTransaction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This database connection does not support creating savepoints.');

        $connection->savepoint('savepoint');
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_creation_throws_exception_when_outside_transaction(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot create savepoint outside of transaction.');

        $this->connection()->savepoint('savepoint');
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_creation_throws_exception_when_duplicate_name(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('duplicate');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Savepoint 'duplicate' already exists at position 0 in transaction level 1.");

        $connection->savepoint('duplicate');
    }

    /**
     * @throws Throwable
     */
    public function test_rollback_to_savepoint_throws_exception_when_unknown_savepoint(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Savepoint 'nonexistent' does not exist in transaction level 1.");

        $connection->rollbackToSavepoint('nonexistent');
    }

    /**
     * @throws Throwable
     */
    public function test_release_savepoint_throws_exception_when_release_unsupported(): void
    {
        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(false);
            });

        $connection->beginTransaction();
        $connection->savepoint('savepoint');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This database connection does not support releasing savepoints.');

        $connection->releaseSavepoint('savepoint');
    }

    /**
     * @throws Throwable
     */
    public function test_release_savepoint_throws_exception_when_unknown_savepoint(): void
    {
        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(true);
            });

        $connection->beginTransaction();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Savepoint 'nonexistent' does not exist in transaction level 1.");

        $connection->releaseSavepoint('nonexistent');
    }

    /**
     * @throws Throwable
     */
    public function test_purge_savepoints_throws_exception_when_purge_unsupported(): void
    {
        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(false);
            });

        $connection->beginTransaction();
        $connection->savepoint('savepoint');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This database connection does not support purging savepoints.');

        $connection->purgeSavepoints();
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_creation_handles_pdo_failure(): void
    {
        $connection = $this->connection([], null, $this->pdo(TestPdo::FAILURE));

        $connection->beginTransaction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create savepoint');

        $connection->savepoint('savepoint');
    }

    /**
     * @throws Throwable
     */
    public function test_rollback_to_savepoint_handles_pdo_failure(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('savepoint');

        $connection = $this->connection(
            [],
            null,
            tap(
                $this->pdo(TestPdo::FAILURE),
                static function ($pdo) use ($connection): void {
                    $pdo->executed = $connection->getPdo()->executed;
                }
            )
        );

        $connection->beginTransaction();
        $connection->savepoints([1 => ['savepoint']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rollback to savepoint');

        $connection->rollbackToSavepoint('savepoint');
    }

    /**
     * @throws Throwable
     */
    public function test_release_savepoint_handles_pdo_failure(): void
    {
        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(true);
            });

        $connection->beginTransaction();
        $connection->savepoint('savepoint');

        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(true);
            },
            tap(
                $this->pdo(TestPdo::FAILURE),
                static function ($pdo) use ($connection): void {
                    $pdo->executed = $connection->getPdo()->executed;
                }
            )
        );

        $connection->beginTransaction();
        $connection->savepoints([1 => ['savepoint']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to release savepoint');

        $connection->releaseSavepoint('savepoint');
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_created_event_fired(): void
    {
        $dispatcher = $this->dispatcher();

        $connection = $this->connection(
            ['getName'],
            static function ($connection) use ($dispatcher): void {
                $connection->method('getName')->willReturn('test_connection');
                $connection->setEventDispatcher($dispatcher);
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint1');

        $this->assertCount(1, $events = $dispatcher->fired());
        $this->assertInstanceOf(SavepointCreated::class, $event = Arr::first($events));
        $this->assertEquals('savepoint1', $event->savepoint);
        $this->assertSame($connection, $event->connection);
        $this->assertEquals('test_connection', $event->connectionName);
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_released_event_fired(): void
    {
        $dispatcher = $this->dispatcher();

        $connection = $this->connection(
            ['getName', 'supportsSavepointRelease'],
            static function ($connection) use ($dispatcher): void {
                $connection->method('getName')->willReturn('test_connection');
                $connection->method('supportsSavepointRelease')->willReturn(true);
                $connection->setEventDispatcher($dispatcher);
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint');
        $connection->releaseSavepoint('savepoint');

        $this->assertCount(2, $events = $dispatcher->fired());
        $this->assertInstanceOf(SavepointReleased::class, $event = Arr::last($events));
        $this->assertEquals('savepoint', $event->savepoint);
        $this->assertSame($connection, $event->connection);
        $this->assertEquals('test_connection', $event->connectionName);
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_rolled_back_event_fired(): void
    {
        $dispatcher = $this->dispatcher();

        $connection = $this->connection(
            ['getName'],
            static function ($connection) use ($dispatcher): void {
                $connection->method('getName')->willReturn('test_connection');
                $connection->setEventDispatcher($dispatcher);
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint1');
        $connection->savepoint('savepoint2');
        $connection->savepoint('savepoint3');
        $connection->rollbackToSavepoint('savepoint1');

        $this->assertCount(4, $events = $dispatcher->fired());
        $this->assertInstanceOf(SavepointRolledBack::class, $event = Arr::last($events));
        $this->assertEquals('savepoint1', $event->savepoint);
        $this->assertEquals(['savepoint2', 'savepoint3'], array_values($event->releasedSavepoints));
        $this->assertSame($connection, $event->connection);
        $this->assertEquals('test_connection', $event->connectionName);
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_callback_success_fires_created_and_released_events(): void
    {
        $dispatcher = $this->dispatcher();

        $connection = $this->connection(
            ['getName', 'transactionLevel', 'supportsSavepointRelease'],
            static function ($connection) use ($dispatcher): void {
                $connection->method('getName')->willReturn('test_connection');
                $connection->method('transactionLevel')->willReturn(1);
                $connection->method('supportsSavepointRelease')->willReturn(true);
                $connection->setEventDispatcher($dispatcher);
            }
        );

        $connection->savepoint('callback_test', static fn (): string => 'success');

        $this->assertCount(2, $events = $dispatcher->fired());
        $this->assertInstanceOf(SavepointCreated::class, $creation = Arr::first($events));
        $this->assertEquals('callback_test', $creation->savepoint);
        $this->assertInstanceOf(SavepointReleased::class, $release = Arr::last($events));
        $this->assertEquals('callback_test', $release->savepoint);
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_callback_failure_fires_created_and_rolled_back_events(): void
    {
        $dispatcher = $this->dispatcher();

        $connection = $this->connection(
            ['getName', 'transactionLevel'],
            static function ($connection) use ($dispatcher): void {
                $connection->method('getName')->willReturn('test_connection');
                $connection->method('transactionLevel')->willReturn(1);
                $connection->setEventDispatcher($dispatcher);
            }
        );

        try {
            $connection->savepoint(
                'savepoint',
                static function (): never {
                    throw new RuntimeException('callback_failed');
                }
            );
        } catch (Exception) {
            // expected
        }

        $events = array_values(
            array_filter(
                $dispatcher->fired(),
                static fn ($event): bool => $event instanceof SavepointCreated || $event instanceof SavepointRolledBack
            )
        );

        $this->assertCount(2, $events);
        $this->assertInstanceOf(SavepointCreated::class, $first = Arr::first($events));
        $this->assertEquals('savepoint', $first->savepoint);
        $this->assertInstanceOf(SavepointRolledBack::class, $last = Arr::last($events));
        $this->assertEquals('savepoint', $last->savepoint);
        $this->assertEquals([], $last->releasedSavepoints);
    }

    /**
     * @throws Throwable
     */
    public function test_has_savepoint(): void
    {
        $connection = $this->connection(
            ['transactionLevel', 'supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
                $connection->method('supportsSavepointRelease')->willReturn(true);
            });

        $this->assertFalse($connection->hasSavepoint('nonexistent'));

        $connection->savepoint('savepoint1');

        $this->assertTrue($connection->hasSavepoint('savepoint1'));
        $this->assertFalse($connection->hasSavepoint('savepoint2'));

        $connection->savepoint('savepoint2');

        $this->assertTrue($connection->hasSavepoint('savepoint1'));
        $this->assertTrue($connection->hasSavepoint('savepoint2'));

        $connection->releaseSavepoint('savepoint1');

        $this->assertFalse($connection->hasSavepoint('savepoint1'));
        $this->assertTrue($connection->hasSavepoint('savepoint2'));
    }

    /**
     * @throws Throwable
     */
    public function test_get_savepoints(): void
    {
        $connection = $this->connection(
            ['transactionLevel', 'supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
                $connection->method('supportsSavepointRelease')->willReturn(true);
            });

        $this->assertEquals([], $connection->getSavepoints());

        $connection->savepoint('savepoint1');

        $this->assertEquals(['savepoint1'], $connection->getSavepoints());

        $connection->savepoint('savepoint2');

        $this->assertEquals(['savepoint1', 'savepoint2'], $connection->getSavepoints());

        $connection->releaseSavepoint('savepoint1');

        $this->assertEquals(['savepoint2'], $connection->getSavepoints());

        $connection->savepoint('savepoint3');

        $this->assertEquals(['savepoint2', 'savepoint3'], $connection->getSavepoints());
    }

    /**
     * @throws Throwable
     */
    public function test_get_current_savepoint(): void
    {
        $connection = $this->connection(
            ['transactionLevel', 'supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
                $connection->method('supportsSavepointRelease')->willReturn(true);
            }
        );

        $this->assertNull($connection->getCurrentSavepoint());

        $connection->savepoint('savepoint1');

        $this->assertEquals('savepoint1', $connection->getCurrentSavepoint());

        $connection->savepoint('savepoint2');

        $this->assertEquals('savepoint2', $connection->getCurrentSavepoint());

        $connection->releaseSavepoint('savepoint2');

        $this->assertEquals('savepoint1', $connection->getCurrentSavepoint());

        $connection->releaseSavepoint('savepoint1');

        $this->assertNull($connection->getCurrentSavepoint());
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_stack_management(): void
    {
        $connection = $this->connection(
            ['transactionLevel'],
            static function ($connection): void {
                $connection->method('transactionLevel')->willReturn(1);
            }
        );

        $connection->savepoint('level1savepoint');
        $connection->savepoint('level2savepoint');
        $connection->savepoint('level3savepoint');

        $this->assertEquals(
            ['level1savepoint', 'level2savepoint', 'level3savepoint'],
            $connection->savepoints(1)
        );
        $this->assertEquals(['level1savepoint', 'level2savepoint', 'level3savepoint'], $connection->getSavepoints());
        $this->assertEquals('level3savepoint', $connection->getCurrentSavepoint());
    }

    /**
     * @throws Throwable
     */
    public function test_nested_transaction_savepoints(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('level1savepoint1');
        $connection->savepoint('level1savepoint2');

        $this->assertEquals(['level1savepoint1', 'level1savepoint2'], $connection->savepoints(1));

        $connection->beginTransaction();
        $connection->savepoint('level2savepoint1');

        $this->assertEquals(['level1savepoint1', 'level1savepoint2'], $connection->savepoints(1));
        $this->assertEquals(['level2savepoint1'], $connection->savepoints(2));
        $this->assertEquals(['level2savepoint1'], $connection->getSavepoints());
        $this->assertEquals('level2savepoint1', $connection->getCurrentSavepoint());
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_cleanup_on_commit(): void
    {
        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(false);
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint1');
        $connection->savepoint('savepoint2');

        $this->assertEquals(['savepoint1', 'savepoint2'], $connection->savepoints(1));

        $connection->commitTransaction();
        $connection->syncSavepoints();

        $this->assertEmpty($connection->savepoints());
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_cleanup_on_rollback(): void
    {
        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(false);
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint1');
        $connection->savepoint('savepoint2');

        $this->assertEquals(['savepoint1', 'savepoint2'], $connection->savepoints(1));

        $connection->rollbackTransaction();
        $connection->syncSavepoints();

        $this->assertEmpty($connection->savepoints());
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_sync_events(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();

        $connection->savepoint('savepoint1');
        $connection->savepoint('savepoint2');

        $this->assertEquals(['savepoint1', 'savepoint2'], $connection->savepoints(1));

        $connection->beginTransaction();
        $connection->syncTransactionBeginning();

        $this->assertEquals(['savepoint1', 'savepoint2'], $connection->savepoints(1));
        $this->assertEquals([], $connection->savepoints(2));

        $connection->beginTransaction();
        $connection->syncTransactionCommitted();

        $this->assertEquals(['savepoint1', 'savepoint2'], $connection->savepoints(1));
        $this->assertEmpty($connection->savepoints(2));
    }

    /**
     * @throws Throwable
     */
    public function test_user_savepoints_isolated_from_internal_trans_savepoints(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('user_savepoint');

        $this->assertEquals(['user_savepoint'], $connection->getSavepoints());

        $connection->beginTransaction();

        $this->assertEmpty($connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('user_savepoint'));
        $this->assertContains('SAVEPOINT "trans2"', $connection->getPdo()->executed);

        $connection->savepoint('trans1');

        $this->assertEquals(['trans1'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('trans1'));
        $this->assertFalse($connection->hasSavepoint('user_savepoint'));

        $connection->commitTransaction();

        $this->assertEquals(['user_savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('user_savepoint'));
        $this->assertFalse($connection->hasSavepoint('trans1'));
    }

    /**
     * @throws Throwable
     */
    public function test_internal_savepoints_not_visible_to_user_methods(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('level1savepoint');
        $connection->beginTransaction();
        $connection->beginTransaction();

        $this->assertContains('SAVEPOINT "trans2"', $connection->getPdo()->executed);
        $this->assertContains('SAVEPOINT "trans3"', $connection->getPdo()->executed);

        $this->assertFalse($connection->hasSavepoint('trans2'));
        $this->assertFalse($connection->hasSavepoint('trans3'));
        $this->assertNotContains('trans2', $connection->getSavepoints());
        $this->assertNotContains('trans3', $connection->getSavepoints());

        $connection->savepoint('level3savepoint');

        $this->assertEquals(['level3savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('level3savepoint'));

        $connection->rollbackTransaction();
        $connection->rollbackTransaction();

        $this->assertEquals(['level1savepoint'], $connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('level3savepoint'));
    }

    /**
     * @throws Throwable
     */
    public function test_user_savepoints_work_with_nested_transactions(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('level1savepoint');

        $this->assertEquals(['level1savepoint'], $connection->getSavepoints());

        $connection->beginTransaction();

        $this->assertEmpty($connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('level1savepoint'));
        $this->assertContains('SAVEPOINT "trans2"', $connection->getPdo()->executed);
        $this->assertContains('SAVEPOINT "'.bin2hex('level1savepoint').'"', $connection->getPdo()->executed);

        $connection->savepoint('level2savepoint');

        $this->assertEquals(['level2savepoint'], $connection->getSavepoints());

        $connection->rollbackTransaction();

        $this->assertEquals(['level1savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('level1savepoint'));
        $this->assertFalse($connection->hasSavepoint('level2savepoint'));

        $connection->rollbackToSavepoint('level1savepoint');

        $this->assertTrue($connection->hasSavepoint('level1savepoint'));
    }

    /**
     * @throws Throwable
     */
    public function test_complex_isolation_with_mixed_savepoints_and_transactions(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('level1savepoint');
        $connection->savepoint('trans1');
        $connection->savepoint('trans2');
        $connection->beginTransaction();

        $this->assertContains('SAVEPOINT "trans2"', $connection->getPdo()->executed);
        $this->assertEmpty($connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('level1savepoint'));
        $this->assertFalse($connection->hasSavepoint('trans1'));
        $this->assertFalse($connection->hasSavepoint('trans2'));

        $connection->savepoint('level2savepoint');
        $connection->savepoint('trans3');
        $connection->beginTransaction();

        $this->assertContains('SAVEPOINT "trans3"', $connection->getPdo()->executed);
        $this->assertEmpty($connection->getSavepoints());

        $connection->savepoint('level3savepoint');

        $this->assertEquals(['level3savepoint'], $connection->getSavepoints());

        $connection->rollbackTransaction();

        $this->assertEquals(['level2savepoint', 'trans3'], $connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('level3savepoint'));

        $connection->rollbackToSavepoint('level2savepoint');

        $this->assertTrue($connection->hasSavepoint('level2savepoint'));
        $this->assertFalse($connection->hasSavepoint('trans3'));

        $connection->commitTransaction();

        $this->assertEquals(['level1savepoint', 'trans1', 'trans2'], $connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('level2savepoint'));
        $this->assertTrue($connection->hasSavepoint('trans1'));
        $this->assertTrue($connection->hasSavepoint('trans2'));
    }

    /**
     * @throws Throwable
     */
    public function test_same_savepoint_name_across_transaction_levels(): void
    {
        $connection = $this->connection();

        $connection->beginTransaction();
        $connection->savepoint('savepoint');

        $this->assertEquals(['savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('savepoint'));

        $connection->beginTransaction();

        $this->assertEmpty($connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('savepoint'));

        $connection->savepoint('savepoint');

        $this->assertEquals(['savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('savepoint'));

        $connection->beginTransaction();

        $this->assertEmpty($connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('savepoint'));

        $connection->savepoint('savepoint');

        $this->assertEquals(['savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('savepoint'));

        $connection->rollbackTransaction();

        $this->assertEquals(['savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('savepoint'));

        $connection->rollbackTransaction();

        $this->assertEquals(['savepoint'], $connection->getSavepoints());
        $this->assertTrue($connection->hasSavepoint('savepoint'));

        $connection->rollbackTransaction();

        $this->assertEmpty($connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('savepoint'));
    }

    /**
     * @throws Throwable
     */
    public function test_savepoints_with_transaction_helper(): void
    {
        $dispatcher = $this->dispatcher();

        $connection = $this->connection(
            ['getName'],
            static function ($connection) use ($dispatcher): void {
                $connection->method('getName')->willReturn('test_connection');
                $connection->setEventDispatcher($dispatcher);
            }
        );

        $result = $connection->transaction(
            function ($connection) {
                $connection->savepoint('nested_savepoint');

                $this->assertTrue($connection->hasSavepoint('nested_savepoint'));
                $this->assertEquals(['nested_savepoint'], $connection->getSavepoints());

                return $connection->savepoint('inner_work', static fn (): string => 'transaction_success');
            }
        );

        $events = array_filter(
            $dispatcher->fired(),
            static function ($event) {
                return str_contains(get_class($event), 'Savepoint');
            }
        );

        $this->assertEquals('transaction_success', $result);
        $this->assertEmpty($connection->getSavepoints());
        $this->assertGreaterThan(0, count($events));
    }

    /**
     * @throws Throwable
     */
    public function test_savepoint_cleanup_after_transaction_rollback(): void
    {
        $connection = $this->connection();

        try {
            $connection->transaction(
                function ($connection) {
                    $connection->savepoint('cleaned1');
                    $connection->savepoint('cleaned2');

                    $this->assertTrue($connection->hasSavepoint('cleaned1'));
                    $this->assertTrue($connection->hasSavepoint('cleaned2'));
                    $this->assertEquals(['cleaned1', 'cleaned2'], $connection->getSavepoints());

                    throw new RuntimeException('Force rollback');
                }
            );
        } catch (RuntimeException) {
            // expected
        }

        $this->assertEmpty($connection->getSavepoints());
        $this->assertFalse($connection->hasSavepoint('cleaned1'));
        $this->assertFalse($connection->hasSavepoint('cleaned2'));
        $this->assertEquals(0, $connection->transactionLevel());
    }

    /**
     * @throws Throwable
     */
    public function test_mysql_grammar_savepoint_sql_generation(): void
    {
        $connection = $this->connection(
            [],
            static function ($connection): void {
                $connection->setQueryGrammar(new MySqlGrammar($connection));
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint');

        $this->assertContains('SAVEPOINT `'.bin2hex('savepoint').'`', $connection->getPdo()->executed);
    }

    /**
     * @throws Throwable
     */
    public function test_postgres_grammar_savepoint_sql_generation(): void
    {
        $connection = $this->connection(
            [],
            static function ($connection): void {
                $connection->setQueryGrammar(new PostgresGrammar($connection));
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint');
        $connection->rollbackToSavepoint('savepoint');

        $executed = $connection->getPdo()->executed;

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint').'"', $executed);
        $this->assertContains('ROLLBACK TO SAVEPOINT "'.bin2hex('savepoint').'"', $executed);
    }

    /**
     * @throws Throwable
     */
    public function test_sqlite_grammar_savepoint_sql_generation(): void
    {
        $connection = $this->connection(
            [],
            static function ($connection): void {
                $connection->setQueryGrammar(new SQLiteGrammar($connection));
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint');
        $connection->rollbackToSavepoint('savepoint');

        $executed = $connection->getPdo()->executed;

        $this->assertContains('SAVEPOINT "'.bin2hex('savepoint').'"', $executed);
        $this->assertContains('ROLLBACK TO "'.bin2hex('savepoint').'"', $executed);
    }

    /**
     * @throws Throwable
     */
    public function test_sqlserver_grammar_savepoint_sql_generation(): void
    {
        $connection = $this->connection(
            [],
            static function ($connection): void {
                $connection->setQueryGrammar(new SqlServerGrammar($connection));
            }
        );
        $connection->beginTransaction();
        $connection->savepoint('savepoint');
        $connection->rollbackToSavepoint('savepoint');

        $executed = $connection->getPdo()->executed;

        $this->assertContains('SAVE TRANSACTION ['.bin2hex('savepoint').']', $executed);
        $this->assertContains('ROLLBACK TRANSACTION ['.bin2hex('savepoint').']', $executed);
    }

    /**
     * @throws Throwable
     */
    public function test_sqlserver_grammar_savepoint_release_throws_exception(): void
    {
        $connection = $this->connection(
            ['supportsSavepointRelease'],
            static function ($connection): void {
                $connection->method('supportsSavepointRelease')->willReturn(false);
                $connection->setQueryGrammar(new SqlServerGrammar($connection));
            }
        );

        $connection->beginTransaction();
        $connection->savepoint('savepoint');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This database connection does not support releasing savepoints.');

        $connection->releaseSavepoint('savepoint');
    }

    /**
     * @throws Throwable
     */
    public function test_grammar_savepoint_support_flags(): void
    {
        $connection = $this->connection();

        $grammars = [
            'mysql' => new MySqlGrammar($connection),
            'postgres' => new PostgresGrammar($connection),
            'sqlite' => new SQLiteGrammar($connection),
            'sqlserver' => new SqlServerGrammar($connection),
        ];

        foreach ($grammars as $type => $grammar) {
            $this->assertTrue($grammar->supportsSavepoints(), "{$type} should support savepoints");

            $type === 'sqlserver'
                ? $this->assertFalse($grammar->supportsSavepointRelease(), "{$type} should not support savepoint release")
                : $this->assertTrue($grammar->supportsSavepointRelease(), "{$type} should support savepoint release");
        }
    }

    protected function connection(array $methods = [], ?callable $callback = null, ?TestPdo $pdo = null): TestConnection
    {
        return tap(
            $this->getMockBuilder(TestConnection::class)
                ->onlyMethods($methods)
                ->setConstructorArgs([$pdo ?? $this->pdo()])
                ->getMock(),
            static function ($mock) use ($callback): void {
                if ($callback) {
                    $callback($mock);
                }
            }
        );
    }

    protected function pdo($mode = TestPdo::SUCCESS): TestPdo
    {
        return new TestPdo($mode);
    }

    protected function dispatcher(): TestDispatcher
    {
        return new TestDispatcher;
    }
}

class TestDispatcher extends Dispatcher
{
    public array $events = [];

    public function dispatch($event, $payload = [], $halt = false): void
    {
        $this->events[] = $event;
    }

    public function fired(): array
    {
        return $this->events;
    }
}

class TestPdo extends PDO
{
    public const SUCCESS = 'success';

    public const FAILURE = 'failure';

    public array $executed = [];

    private string $mode;

    public function __construct($mode = self::SUCCESS)
    {
        $this->mode = $mode;
    }

    public function exec($statement): int|false
    {
        $this->executed[] = $statement;

        return $this->mode === self::FAILURE ? false : 0;
    }

    public function beginTransaction(): true
    {
        return true;
    }

    public function commit(): true
    {
        return true;
    }

    public function rollBack(): true
    {
        return true;
    }
}

class TestConnection extends Connection
{
    public function __construct(?TestPdo $pdo = null)
    {
        parent::__construct($pdo ?? new TestPdo);

        $this->useDefaultQueryGrammar();
    }

    public function getPdo(): PDO|TestPdo
    {
        return $this->pdo;
    }

    public function savepoints(array|int|null $savepointsOrLevel = null): array
    {
        return match (true) {
            is_array($savepointsOrLevel) => $this->savepoints = $savepointsOrLevel,
            is_null($savepointsOrLevel) => $this->savepoints,
            default => $this->savepoints[$savepointsOrLevel] ?? [],
        };
    }

    public function syncSavepoints(): void
    {
        parent::syncSavepoints();
    }

    public function syncTransactionBeginning(): void
    {
        parent::syncTransactionBeginning();
    }

    public function syncTransactionCommitted(): void
    {
        parent::syncTransactionCommitted();
    }

    public function beginTransaction()
    {
        return ++$this->transactions === 1
            ? $this->pdo->beginTransaction()
            : $this->pdo->exec($this->queryGrammar->compileSavepoint('trans'.$this->transactions));
    }

    public function commitTransaction(): void
    {
        if ($this->transactions === 1) {
            $this->pdo->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);
    }

    public function rollbackTransaction($toLevel = null): void
    {
        $toLevel ??= $this->transactions - 1;

        $toLevel === 0
            ? $this->pdo->rollBack()
            : $this->pdo->exec($this->queryGrammar->compileRollbackToSavepoint('trans'.($toLevel + 1)));

        $this->transactions = $toLevel;
    }

    public function transactionLevel(): int
    {
        return $this->transactions;
    }
}
