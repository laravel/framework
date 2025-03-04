<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration]
class EloquentNamedScopeAttibuteTest extends TestCase
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

    public function test_it_can_query_named_scoped_from_the_query_builder()
    {
        $query = Fixtures\NamedScopeUser::query()->verified(true);

        $this->assertSame($this->query, $query->toRawSql());
    }

    public function test_it_can_query_named_scoped_from_static_query()
    {
        $query = Fixtures\NamedScopeUser::verified(true);

        $this->assertSame($this->query, $query->toRawSql());
    }
}
