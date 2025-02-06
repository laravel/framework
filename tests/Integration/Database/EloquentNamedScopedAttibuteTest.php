<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithConfig('database.default', 'testing')]
#[WithMigration]
class EloquentNamedScopedAttibuteTest extends TestCase
{
    public function test_it_can_query_named_scoped_from_the_query_builder()
    {
        $query = Fixtures\NamedScopedUser::query()->verified(true);

        $this->assertSame(
            'select * from "named_scoped_users" where "email_verified_at" is not null',
            $query->toRawSql(),
        );
    }

    public function test_it_can_query_named_scoped_from_static_query()
    {
        $query = Fixtures\NamedScopedUser::verified(true);

        $this->assertSame(
            'select * from "named_scoped_users" where "email_verified_at" is not null',
            $query->toRawSql(),
        );
    }
}
