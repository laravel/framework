<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Database\Schema\MariaDbBuilder;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Database\Schema\SqlServerBuilder;
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
        $conn = $this->getConnection();
        $conn->shouldReceive('statement')->once()->with('foo');
        $conn->shouldReceive('statement')->once()->with('bar');
        $blueprint = $this->getMockBuilder(Blueprint::class)->onlyMethods(['toSql'])->setConstructorArgs([$conn, 'users'])->getMock();
        $blueprint->expects($this->once())->method('toSql')->willReturn(['foo', 'bar']);

        $blueprint->build();
    }

    public function testIndexDefaultNames()
    {
        $blueprint = $this->getBlueprint(table: 'users');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_bar_unique', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'users');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_index', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'geo');
        $blueprint->spatialIndex('coordinates');
        $commands = $blueprint->getCommands();
        $this->assertSame('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testIndexDefaultNamesWhenPrefixSupplied()
    {
        $blueprint = $this->getBlueprint(table: 'users', prefix: 'prefix_');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_bar_unique', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'users', prefix: 'prefix_');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_index', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'geo', prefix: 'prefix_');
        $blueprint->spatialIndex('coordinates');
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDropIndexDefaultNames()
    {
        $blueprint = $this->getBlueprint(table: 'users');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_bar_unique', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'users');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_index', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $commands = $blueprint->getCommands();
        $this->assertSame('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDropIndexDefaultNamesWhenPrefixSupplied()
    {
        $blueprint = $this->getBlueprint(table: 'users', prefix: 'prefix_');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_bar_unique', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'users', prefix: 'prefix_');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_index', $commands[0]->index);

        $blueprint = $this->getBlueprint(table: 'geo', prefix: 'prefix_');
        $blueprint->dropSpatialIndex(['coordinates']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDefaultCurrentDateTime()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'users', function ($table) {
                $table->dateTime('created')->useCurrent();
            })->toSql();
        };

        $this->assertEquals(['alter table `users` add `created` datetime not null default CURRENT_TIMESTAMP'], $getSql(new MySqlGrammar));
        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone not null default CURRENT_TIMESTAMP'], $getSql(new PostgresGrammar));
        $this->assertEquals(['alter table "users" add column "created" datetime not null default CURRENT_TIMESTAMP'], $getSql(new SQLiteGrammar));
        $this->assertEquals(['alter table "users" add "created" datetime not null default CURRENT_TIMESTAMP'], $getSql(new SqlServerGrammar));
    }

    public function testDefaultCurrentTimestamp()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'users', function ($table) {
                $table->timestamp('created')->useCurrent();
            })->toSql();
        };

        $this->assertEquals(['alter table `users` add `created` timestamp not null default CURRENT_TIMESTAMP'], $getSql(new MySqlGrammar));
        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone not null default CURRENT_TIMESTAMP'], $getSql(new PostgresGrammar));
        $this->assertEquals(['alter table "users" add column "created" datetime not null default CURRENT_TIMESTAMP'], $getSql(new SQLiteGrammar));
        $this->assertEquals(['alter table "users" add "created" datetime not null default CURRENT_TIMESTAMP'], $getSql(new SqlServerGrammar));
    }

    public function testRemoveColumn()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'users', function ($table) {
                $table->string('foo');
                $table->string('remove_this');
                $table->removeColumn('remove_this');
            })->toSql();
        };

        $this->assertEquals(['alter table `users` add `foo` varchar(255) not null'], $getSql(new MySqlGrammar));
    }

    public function testRenameColumn()
    {
        $getSql = function ($grammar) {
            $connection = $this->getConnection($grammar);
            $connection->shouldReceive('getServerVersion')->andReturn('8.0.4');
            $connection->shouldReceive('isMaria')->andReturn(false);

            return (new Blueprint($connection, 'users', function ($table) {
                $table->renameColumn('foo', 'bar');
            }))->toSql();
        };

        $this->assertEquals(['alter table `users` rename column `foo` to `bar`'], $getSql(new MySqlGrammar));
        $this->assertEquals(['alter table "users" rename column "foo" to "bar"'], $getSql(new PostgresGrammar));
        $this->assertEquals(['alter table "users" rename column "foo" to "bar"'], $getSql(new SQLiteGrammar));
        $this->assertEquals(['sp_rename N\'"users"."foo"\', "bar", N\'COLUMN\''], $getSql(new SqlServerGrammar));
    }

    public function testNativeRenameColumnOnMysql57()
    {
        $connection = $this->getConnection(new MySqlGrammar);
        $connection->shouldReceive('isMaria')->andReturn(false);
        $connection->shouldReceive('getServerVersion')->andReturn('5.7');
        $connection->getSchemaBuilder()->shouldReceive('getColumns')->andReturn([
            ['name' => 'name', 'type' => 'varchar(255)', 'type_name' => 'varchar', 'nullable' => true, 'collation' => 'utf8mb4_unicode_ci', 'default' => 'foo', 'comment' => null, 'auto_increment' => false, 'generation' => null],
            ['name' => 'id', 'type' => 'bigint unsigned', 'type_name' => 'bigint', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => 'lorem ipsum', 'auto_increment' => true, 'generation' => null],
            ['name' => 'generated', 'type' => 'int', 'type_name' => 'int', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => null, 'auto_increment' => false, 'generation' => ['type' => 'stored', 'expression' => 'expression']],
        ]);

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->renameColumn('name', 'title');
            $table->renameColumn('id', 'key');
            $table->renameColumn('generated', 'new_generated');
        });

        $this->assertEquals([
            "alter table `users` change `name` `title` varchar(255) collate 'utf8mb4_unicode_ci' null default 'foo'",
            "alter table `users` change `id` `key` bigint unsigned not null auto_increment comment 'lorem ipsum'",
            'alter table `users` change `generated` `new_generated` int as (expression) stored not null',
        ], $blueprint->toSql());
    }

    public function testNativeRenameColumnOnLegacyMariaDB()
    {
        $connection = $this->getConnection(new MariaDbGrammar);
        $connection->shouldReceive('isMaria')->andReturn(true);
        $connection->shouldReceive('getServerVersion')->andReturn('10.1.35');
        $connection->getSchemaBuilder()->shouldReceive('getColumns')->andReturn([
            ['name' => 'name', 'type' => 'varchar(255)', 'type_name' => 'varchar', 'nullable' => true, 'collation' => 'utf8mb4_unicode_ci', 'default' => 'foo', 'comment' => null, 'auto_increment' => false, 'generation' => null],
            ['name' => 'id', 'type' => 'bigint unsigned', 'type_name' => 'bigint', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => 'lorem ipsum', 'auto_increment' => true, 'generation' => null],
            ['name' => 'generated', 'type' => 'int', 'type_name' => 'int', 'nullable' => false, 'collation' => null, 'default' => null, 'comment' => null, 'auto_increment' => false, 'generation' => ['type' => 'stored', 'expression' => 'expression']],
            ['name' => 'foo', 'type' => 'int', 'type_name' => 'int', 'nullable' => true, 'collation' => null, 'default' => 'NULL', 'comment' => null, 'auto_increment' => false, 'generation' => null],
        ]);

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->renameColumn('name', 'title');
            $table->renameColumn('id', 'key');
            $table->renameColumn('generated', 'new_generated');
            $table->renameColumn('foo', 'bar');
        });

        $this->assertEquals([
            "alter table `users` change `name` `title` varchar(255) collate 'utf8mb4_unicode_ci' null default 'foo'",
            "alter table `users` change `id` `key` bigint unsigned not null auto_increment comment 'lorem ipsum'",
            'alter table `users` change `generated` `new_generated` int as (expression) stored not null',
            'alter table `users` change `foo` `bar` int null default NULL',
        ], $blueprint->toSql());
    }

    public function testDropColumn()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'users', function ($table) {
                $table->dropColumn('foo');
            })->toSql();
        };

        $this->assertEquals(['alter table `users` drop `foo`'], $getSql(new MySqlGrammar));
        $this->assertEquals(['alter table "users" drop column "foo"'], $getSql(new PostgresGrammar));
        $this->assertEquals(['alter table "users" drop column "foo"'], $getSql(new SQLiteGrammar));
        $this->assertStringContainsString('alter table "users" drop column "foo"', $getSql(new SqlServerGrammar)[0]);
    }

    public function testMacroable()
    {
        Blueprint::macro('foo', function () {
            return $this->addCommand('foo');
        });

        MySqlGrammar::macro('compileFoo', function () {
            return 'bar';
        });

        $blueprint = $this->getBlueprint(new MySqlGrammar, 'users', function ($table) {
            $table->foo();
        });

        $this->assertEquals(['bar'], $blueprint->toSql());
    }

    public function testDefaultUsingIdMorph()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'comments', function ($table) {
                $table->morphs('commentable');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null',
            'alter table `comments` add `commentable_id` bigint unsigned not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $getSql(new MySqlGrammar));
    }

    public function testDefaultUsingNullableIdMorph()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'comments', function ($table) {
                $table->nullableMorphs('commentable');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null',
            'alter table `comments` add `commentable_id` bigint unsigned null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $getSql(new MySqlGrammar));
    }

    public function testDefaultUsingUuidMorph()
    {
        Builder::defaultMorphKeyType('uuid');

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'comments', function ($table) {
                $table->morphs('commentable');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null',
            'alter table `comments` add `commentable_id` char(36) not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $getSql(new MySqlGrammar));
    }

    public function testDefaultUsingNullableUuidMorph()
    {
        Builder::defaultMorphKeyType('uuid');

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'comments', function ($table) {
                $table->nullableMorphs('commentable');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null',
            'alter table `comments` add `commentable_id` char(36) null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $getSql(new MySqlGrammar));
    }

    public function testDefaultUsingUlidMorph()
    {
        Builder::defaultMorphKeyType('ulid');

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'comments', function ($table) {
                $table->morphs('commentable');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null',
            'alter table `comments` add `commentable_id` char(26) not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $getSql(new MySqlGrammar));
    }

    public function testDefaultUsingNullableUlidMorph()
    {
        Builder::defaultMorphKeyType('ulid');

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'comments', function ($table) {
                $table->nullableMorphs('commentable');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null',
            'alter table `comments` add `commentable_id` char(26) null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $getSql(new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithIncrementalModel()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->foreignIdFor('Illuminate\Foundation\Auth\User');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `posts` add `user_id` bigint unsigned not null',
        ], $getSql(new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithUuidModel()
    {
        require_once __DIR__.'/stubs/EloquentModelUuidStub.php';

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->foreignIdFor('EloquentModelUuidStub');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `posts` add `eloquent_model_uuid_stub_id` char(36) not null',
        ], $getSql(new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithUlidModel()
    {
        require_once __DIR__.'/stubs/EloquentModelUlidStub.php';

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->foreignIdFor('EloquentModelUlidStub');
            })->toSql();
        };

        $this->assertEquals([
            'alter table "posts" add column "eloquent_model_ulid_stub_id" char(26) not null',
        ], $getSql(new PostgresGrammar));

        $this->assertEquals([
            'alter table `posts` add `eloquent_model_ulid_stub_id` char(26) not null',
        ], $getSql(new MySqlGrammar));
    }

    public function testDropRelationshipColumnWithIncrementalModel()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->dropForeignIdFor('Illuminate\Foundation\Auth\User');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_user_id_foreign`',
        ], $getSql(new MySqlGrammar));
    }

    public function testDropRelationshipColumnWithUuidModel()
    {
        require_once __DIR__.'/stubs/EloquentModelUuidStub.php';

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->dropForeignIdFor('EloquentModelUuidStub');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_eloquent_model_uuid_stub_id_foreign`',
        ], $getSql(new MySqlGrammar));
    }

    public function testDropConstrainedRelationshipColumnWithIncrementalModel()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->dropConstrainedForeignIdFor('Illuminate\Foundation\Auth\User');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_user_id_foreign`',
            'alter table `posts` drop `user_id`',
        ], $getSql(new MySqlGrammar));
    }

    public function testDropConstrainedRelationshipColumnWithUuidModel()
    {
        require_once __DIR__.'/stubs/EloquentModelUuidStub.php';

        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->dropConstrainedForeignIdFor('EloquentModelUuidStub');
            })->toSql();
        };

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_eloquent_model_uuid_stub_id_foreign`',
            'alter table `posts` drop `eloquent_model_uuid_stub_id`',
        ], $getSql(new MySqlGrammar));
    }

    public function testTinyTextColumn()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->tinyText('note');
            })->toSql();
        };

        $this->assertEquals(['alter table `posts` add `note` tinytext not null'], $getSql(new MySqlGrammar));
        $this->assertEquals(['alter table "posts" add column "note" text not null'], $getSql(new SQLiteGrammar));
        $this->assertEquals(['alter table "posts" add column "note" varchar(255) not null'], $getSql(new PostgresGrammar));
        $this->assertEquals(['alter table "posts" add "note" nvarchar(255) not null'], $getSql(new SqlServerGrammar));
    }

    public function testTinyTextNullableColumn()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->tinyText('note')->nullable();
            })->toSql();
        };

        $this->assertEquals(['alter table `posts` add `note` tinytext null'], $getSql(new MySqlGrammar));
        $this->assertEquals(['alter table "posts" add column "note" text'], $getSql(new SQLiteGrammar));
        $this->assertEquals(['alter table "posts" add column "note" varchar(255) null'], $getSql(new PostgresGrammar));
        $this->assertEquals(['alter table "posts" add "note" nvarchar(255) null'], $getSql(new SqlServerGrammar));
    }

    public function testTableComment()
    {
        $getSql = function ($grammar) {
            return $this->getBlueprint($grammar, 'posts', function ($table) {
                $table->comment('Look at my comment, it is amazing');
            })->toSql();
        };

        $this->assertEquals(['alter table `posts` comment = \'Look at my comment, it is amazing\''], $getSql(new MySqlGrammar));
        $this->assertEquals(['comment on table "posts" is \'Look at my comment, it is amazing\''], $getSql(new PostgresGrammar));
    }

    protected function getConnection(?Grammar $grammar = null)
    {
        $grammar ??= new MySqlGrammar;

        $builder = mock(match ($grammar::class) {
            MySqlGrammar::class => MySqlBuilder::class,
            PostgresGrammar::class => PostgresBuilder::class,
            SQLiteGrammar::class => SQLiteBuilder::class,
            SqlServerGrammar::class => SqlServerBuilder::class,
            MariaDbGrammar::class => MariaDbBuilder::class,
            default => Builder::class,
        });

        $connection = m::mock(Connection::class)
            ->shouldReceive('getSchemaGrammar')->andReturn($grammar)
            ->shouldReceive('getSchemaBuilder')->andReturn($builder);

        if ($grammar instanceof SQLiteGrammar) {
            $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        }

        return $connection->getMock();
    }

    protected function getBlueprint(
        ?Grammar $grammar = null,
        string $table = '',
        ?Closure $callback = null,
        string $prefix = ''
    ): Blueprint {
        $connection = $this->getConnection($grammar);

        return new Blueprint($connection, $table, $callback, $prefix);
    }
}
