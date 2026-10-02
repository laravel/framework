<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDOException;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_sqlsrv')]
class DatabaseSqlServerSavepointRollbackTest extends SqlServerTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('savepoint_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('savepoint_guarded', function (Blueprint $table) {
            $table->id();
            $table->integer('value');
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER savepoint_guarded_reject_negative ON savepoint_guarded AFTER INSERT AS
            BEGIN
                IF EXISTS (SELECT 1 FROM inserted WHERE value < 0)
                BEGIN
                    RAISERROR('value must not be negative', 16, 1);
                    ROLLBACK TRANSACTION;
                END
            END
        SQL);

        DB::table('savepoint_tags')->insert(['name' => 'existing']);
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('savepoint_guarded');
        Schema::drop('savepoint_tags');
    }

    public function testNestedTransactionRethrowsTheErrorThatRolledBackTheWholeTransaction()
    {
        try {
            DB::transaction(function () {
                DB::transaction(function () {
                    // MERGE error 8672: a target row matches more than one source row. SQL Server
                    // answers it by rolling back the entire transaction, savepoints included.
                    DB::table('savepoint_tags')->upsert([['name' => 'existing'], ['name' => 'existing']], 'name');
                });
            });

            $this->fail('The upsert should have failed.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('MERGE statement', $e->getMessage());
        }

        $this->assertConnectionHasNoTransaction();
    }

    public function testNestedTransactionRethrowsTheErrorRaisedByATriggerThatRollsBack()
    {
        try {
            DB::transaction(function () {
                DB::transaction(function () {
                    DB::table('savepoint_guarded')->insert(['value' => -1]);
                });
            });

            $this->fail('The insert should have failed.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('value must not be negative', $e->getMessage());
        }

        $this->assertConnectionHasNoTransaction();
    }

    public function testLaterTransactionsCommitAfterTheServerRolledBackTheWholeTransaction()
    {
        try {
            DB::transaction(function () {
                DB::transaction(function () {
                    DB::table('savepoint_guarded')->insert(['value' => -1]);
                });
            });
        } catch (QueryException) {
            //
        }

        DB::transaction(function () {
            DB::table('savepoint_guarded')->insert(['value' => 1]);
        });

        DB::purge();

        $this->assertSame(1, DB::table('savepoint_guarded')->where('value', 1)->count());
    }

    public function testManualRollbackResetsTheTransactionLevelWhenTheSavepointNoLongerExists()
    {
        DB::beginTransaction();
        DB::beginTransaction();

        try {
            DB::table('savepoint_guarded')->insert(['value' => -1]);

            $this->fail('The insert should have failed.');
        } catch (QueryException) {
            try {
                DB::rollBack();

                $this->fail('Rolling back to a savepoint that no longer exists should fail.');
            } catch (PDOException $e) {
                $this->assertStringContainsString('trans2', $e->getMessage());
            }
        }

        $this->assertConnectionHasNoTransaction();
    }

    protected function assertConnectionHasNoTransaction(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame(0, (int) DB::selectOne('select @@trancount as trancount')->trancount);
    }
}
