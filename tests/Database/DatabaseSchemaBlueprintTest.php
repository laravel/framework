<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Tests\Database\Fixtures\Models\User;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBlueprintTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Builder::$defaultMorphKeyType = 'int';
    }

    public function testToSqlRunsCommandsFromBlueprint()
    {
        $conn = m::mock(Connection::class);
        $conn->shouldReceive('statement')->once()->with('foo');
        $conn->shouldReceive('statement')->once()->with('bar');
        $grammar = m::mock(MySqlGrammar::class);
        $blueprint = $this->getMockBuilder(Blueprint::class)->onlyMethods(['toSql'])->setConstructorArgs(['users'])->getMock();
        $blueprint->expects($this->once())->method('toSql')->with($this->equalTo($conn), $this->equalTo($grammar))->willReturn(['foo', 'bar']);

        $blueprint->build($conn, $grammar);
    }

    public function testIndexDefaultNames()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $commands = $blueprint->getCommands();
        $this->assertSame('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testIndexDefaultNamesWhenPrefixSupplied()
    {
        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo', null, 'prefix_');
        $blueprint->spatialIndex('coordinates');
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDropIndexDefaultNames()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $commands = $blueprint->getCommands();
        $this->assertSame('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDropIndexDefaultNamesWhenPrefixSupplied()
    {
        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo', null, 'prefix_');
        $blueprint->dropSpatialIndex(['coordinates']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDefaultCurrentDateTime()
    {
        $base = new Blueprint('users', function ($table) {
            $table->dateTime('created')->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;
        $this->assertEquals(['alter table `users` add `created` datetime not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        $this->assertEquals(['alter table "users" add column "created" datetime not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add "created" datetime not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testDefaultCurrentTimestamp()
    {
        $base = new Blueprint('users', function ($table) {
            $table->timestamp('created')->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;
        $this->assertEquals(['alter table `users` add `created` timestamp not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        $this->assertEquals(['alter table "users" add column "created" datetime not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add "created" datetime not null default CURRENT_TIMESTAMP'], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testRemoveColumn()
    {
        $base = new Blueprint('users', function ($table) {
            $table->string('foo');
            $table->string('remove_this');
            $table->removeColumn('remove_this');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals(['alter table `users` add `foo` varchar(255) not null'], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testRenameColumn()
    {
        $base = new Blueprint('users', function ($table) {
            $table->renameColumn('foo', 'bar');
        });

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getServerVersion')->andReturn('8.0.4');
        $connection->shouldReceive('isMaria')->andReturn(false);

        $blueprint = clone $base;
        $this->assertEquals(['alter table `users` rename column `foo` to `bar`'], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" rename column "foo" to "bar"'], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" rename column "foo" to "bar"'], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['sp_rename N\'"users"."foo"\', "bar", N\'COLUMN\''], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testNativeRenameColumnOnMysql57()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->renameColumn('name', 'title');
            $table->renameColumn('id', 'key');
            $table->renameColumn('generated', 'new_generated');
        });

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('isMaria')->andReturn(false);
        $connection->shouldReceive('getServerVersion')->andReturn('5.7');
        $connection->shouldReceive('getSchemaBuilder->getColumns')->andReturn([
            ['name' => 'name', 'type' => 'varchar(255)', 'type_name' => 'varchar', 'nullable' => true, 'collation' => 'utf8mb4_unicode_ci', 'default' => 'foo', 'comment' => null, 'auto_increment' => false, 'generation' => null],
            ['name' => 'id', 'type' => 'bigint unsigned', 'type_name' => 'bigint', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => 'lorem ipsum', 'auto_increment' => true, 'generation' => null],
            ['name' => 'generated', 'type' => 'int', 'type_name' => 'int', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => null, 'auto_increment' => false, 'generation' => ['type' => 'stored', 'expression' => 'expression']],
        ]);

        $this->assertEquals([
            "alter table `users` change `name` `title` varchar(255) collate 'utf8mb4_unicode_ci' null default 'foo'",
            "alter table `users` change `id` `key` bigint unsigned not null auto_increment comment 'lorem ipsum'",
            'alter table `users` change `generated` `new_generated` int as (expression) stored not null',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testNativeRenameColumnOnLegacyMariaDB()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->renameColumn('name', 'title');
            $table->renameColumn('id', 'key');
            $table->renameColumn('generated', 'new_generated');
            $table->renameColumn('foo', 'bar');
        });

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('isMaria')->andReturn(true);
        $connection->shouldReceive('getServerVersion')->andReturn('10.1.35');
        $connection->shouldReceive('getSchemaBuilder->getColumns')->andReturn([
            ['name' => 'name', 'type' => 'varchar(255)', 'type_name' => 'varchar', 'nullable' => true, 'collation' => 'utf8mb4_unicode_ci', 'default' => 'foo', 'comment' => null, 'auto_increment' => false, 'generation' => null],
            ['name' => 'id', 'type' => 'bigint unsigned', 'type_name' => 'bigint', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => 'lorem ipsum', 'auto_increment' => true, 'generation' => null],
            ['name' => 'generated', 'type' => 'int', 'type_name' => 'int', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => null, 'auto_increment' => false, 'generation' => ['type' => 'stored', 'expression' => 'expression']],
            ['name' => 'foo', 'type' => 'int', 'type_name' => 'int', 'nullable' => true, 'collation' => null, 'default' => 'NULL', 'comment' => null, 'auto_increment' => false, 'generation' => null],
        ]);

        $this->assertEquals([
            "alter table `users` change `name` `title` varchar(255) collate 'utf8mb4_unicode_ci' null default 'foo'",
            "alter table `users` change `id` `key` bigint unsigned not null auto_increment comment 'lorem ipsum'",
            'alter table `users` change `generated` `new_generated` int as (expression) stored not null',
            'alter table `users` change `foo` `bar` int null default NULL',
        ], $blueprint->toSql($connection, new MariaDbGrammar));
    }

    public function testDropColumn()
    {
        $base = new Blueprint('users', function ($table) {
            $table->dropColumn('foo');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;
        $this->assertEquals(['alter table `users` drop `foo`'], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" drop column "foo"'], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        $this->assertEquals(['alter table "users" drop column "foo"'], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertStringContainsString('alter table "users" drop column "foo"', $blueprint->toSql($connection, new SqlServerGrammar)[0]);
    }

    public function testMacroable()
    {
        Blueprint::macro('foo', function () {
            return $this->addCommand('foo');
        });

        MySqlGrammar::macro('compileFoo', function () {
            return 'bar';
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->foo();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['bar'], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingIdMorph()
    {
        $base = new Blueprint('comments', function ($table) {
            $table->morphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null',
            'alter table `comments` add `commentable_id` bigint unsigned not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingNullableIdMorph()
    {
        $base = new Blueprint('comments', function ($table) {
            $table->nullableMorphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null',
            'alter table `comments` add `commentable_id` bigint unsigned null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingUuidMorph()
    {
        Builder::defaultMorphKeyType('uuid');

        $base = new Blueprint('comments', function ($table) {
            $table->morphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null',
            'alter table `comments` add `commentable_id` char(36) not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingNullableUuidMorph()
    {
        Builder::defaultMorphKeyType('uuid');

        $base = new Blueprint('comments', function ($table) {
            $table->nullableMorphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null',
            'alter table `comments` add `commentable_id` char(36) null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingUlidMorph()
    {
        Builder::defaultMorphKeyType('ulid');

        $base = new Blueprint('comments', function ($table) {
            $table->morphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null',
            'alter table `comments` add `commentable_id` char(26) not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingNullableUlidMorph()
    {
        Builder::defaultMorphKeyType('ulid');

        $base = new Blueprint('comments', function ($table) {
            $table->nullableMorphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null',
            'alter table `comments` add `commentable_id` char(26) null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithIncrementalModel()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->foreignIdFor('Illuminate\Foundation\Auth\User');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` add `user_id` bigint unsigned not null',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithNonIncrementalModel()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->foreignIdFor(Fixtures\Models\EloquentModelUsingNonIncrementedInt::class);
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` add `model_using_non_incremented_int_id` bigint unsigned not null',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithUuidModel()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->foreignIdFor(Fixtures\Models\EloquentModelUsingUuid::class);
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` add `model_using_uuid_id` char(36) not null',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithUlidModel()
    {
        $base = new Blueprint('posts', function (Blueprint $table) {
            $table->foreignIdFor(Fixtures\Models\EloquentModelUsingUlid::class);
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table "posts" add column "model_using_ulid_id" char(26) not null',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` add `model_using_ulid_id` char(26) not null',
        ], $blueprint->toSql($connection, new MySqlGrammar()));
    }

    public function testGenerateRelationshipConstrainedColumn()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->foreignIdFor('Illuminate\Foundation\Auth\User')->constrained();
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` add `user_id` bigint unsigned not null',
            'alter table `posts` add constraint `posts_user_id_foreign` foreign key (`user_id`) references `users` (`id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testGenerateRelationshipForModelWithNonStandardPrimaryKeyName()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->foreignIdFor(User::class)->constrained();
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` add `user_internal_id` bigint unsigned not null',
            'alter table `posts` add constraint `posts_user_internal_id_foreign` foreign key (`user_internal_id`) references `users` (`internal_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropRelationshipColumnWithIncrementalModel()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->dropForeignIdFor('Illuminate\Foundation\Auth\User');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_user_id_foreign`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropRelationshipColumnWithUuidModel()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->dropForeignIdFor(Fixtures\Models\EloquentModelUsingUuid::class);
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_model_using_uuid_id_foreign`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropConstrainedRelationshipColumnWithIncrementalModel()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->dropConstrainedForeignIdFor('Illuminate\Foundation\Auth\User');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_user_id_foreign`',
            'alter table `posts` drop `user_id`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropConstrainedRelationshipColumnWithUuidModel()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->dropConstrainedForeignIdFor(Fixtures\Models\EloquentModelUsingUuid::class);
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_model_using_uuid_id_foreign`',
            'alter table `posts` drop `model_using_uuid_id`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testTinyTextColumn()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->tinyText('note');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table `posts` add `note` tinytext not null',
        ], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        $this->assertEquals([
            'alter table "posts" add column "note" text not null',
        ], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table "posts" add column "note" varchar(255) not null',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table "posts" add "note" nvarchar(255) not null',
        ], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testTinyTextNullableColumn()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->tinyText('note')->nullable();
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table `posts` add `note` tinytext null',
        ], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        $this->assertEquals([
            'alter table "posts" add column "note" text',
        ], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table "posts" add column "note" varchar(255) null',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table "posts" add "note" nvarchar(255) null',
        ], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testRawColumn()
    {
        $base = new Blueprint('posts', function ($table) {
            $table->rawColumn('legacy_boolean', 'INT(1)')->nullable();
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table `posts` add `legacy_boolean` INT(1) null',
        ], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        $this->assertEquals([
            'alter table "posts" add column "legacy_boolean" INT(1)',
        ], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table "posts" add column "legacy_boolean" INT(1) null',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table "posts" add "legacy_boolean" INT(1) null',
        ], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testTableComment()
    {
        $base = new Blueprint('posts', function (Blueprint $table) {
            $table->comment('Look at my comment, it is amazing');
        });

        $connection = m::mock(Connection::class);

        $blueprint = clone $base;
        $this->assertEquals([
            'alter table `posts` comment = \'Look at my comment, it is amazing\'',
        ], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $this->assertEquals([
            'comment on table "posts" is \'Look at my comment, it is amazing\'',
        ], $blueprint->toSql($connection, new PostgresGrammar));
    }
}
