<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

#[WithMigration]
class EloquentScopeAttributeTest extends TestCase
{
    protected $query = 'select * from "scope_users" where "email_verified_at" is not null';

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkippedUnless(
            $this->usesSqliteInMemoryDatabaseConnection(),
            'Requires in-memory database connection',
        );
    }

    #[DataProvider('scopeDataProvider')]
    public function test_it_can_query_scoped_from_the_query_builder(string $methodName)
    {
        $query = Fixtures\ScopeUser::query()->{$methodName}(true);

        $this->assertSame($this->query, $query->toRawSql());
    }

    #[DataProvider('scopeDataProvider')]
    public function test_it_can_query_scoped_from_static_query(string $methodName)
    {
        $query = Fixtures\ScopeUser::{$methodName}(true);

        $this->assertSame($this->query, $query->toRawSql());
    }

    public static function scopeDataProvider(): array
    {
        return [
            'scope with return' => ['verified'],
            'scope without return' => ['verifiedWithoutReturn'],
        ];
    }
}
