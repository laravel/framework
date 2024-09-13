<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Tests\Integration\Database\Traits\EloquentBulkInsertTestTrait;
use Illuminate\Tests\Integration\Database\Traits\CreatesUniqueUsersUUIDTable;

class DatabaseEloquentSqliteIntegrationTest extends SqliteTestCase
{
    use EloquentBulkInsertTestTrait;
    use CreatesUniqueUsersUUIDTable;

    protected function afterRefreshingDatabase(): void
    {
        $this->createUniqueUsersUUIDTable();
    }

    protected function destroyDatabaseMigrations(): void
    {
        $this->dropUniqueUsersUUIDTable();
    }
}
