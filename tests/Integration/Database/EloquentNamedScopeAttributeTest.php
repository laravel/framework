<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

#[WithMigration]
class EloquentNamedScopeAttributeTest extends TestCase
{
    protected $query = 'select * from "named_scope_users" where "email_verified_at" is not null';

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkippedUnless(
            $this->usesSqliteInMemoryDatabaseConnection(),
            'Requires in-memory database connection',
        );
    }

    #[DataProvider('namedScopeDataProvider')]
    public function test_it_can_query_named_scoped_from_the_query_builder(string $methodName)
    {
        $query = Fixtures\NamedScopeUser::query()->{$methodName}(true);

        $this->assertSame($this->query, $query->toRawSql());
    }

    #[DataProvider('namedScopeDataProvider')]
    public function test_it_can_query_named_scoped_from_static_query(string $methodName)
    {
        $query = Fixtures\NamedScopeUser::{$methodName}(true);

        $this->assertSame($this->query, $query->toRawSql());
    }

    public static function namedScopeDataProvider(): array
    {
        return [
            'scope with return' => ['verified'],
            'scope without return' => ['verifiedWithoutReturn'],
        ];
    }
}
