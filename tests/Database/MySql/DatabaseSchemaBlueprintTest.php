<?php

namespace Illuminate\Tests\Database\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
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

    public function testDefaultCurrentDateTime()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->dateTime('created')->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['alter table `users` add `created` datetime default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultCurrentTimestamp()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->timestamp('created')->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['alter table `users` add `created` timestamp default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testUnsignedDecimalTable()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->unsignedDecimal('money', 10, 2)->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['alter table `users` add `money` decimal(10, 2) unsigned not null'], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testRemoveColumn()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->string('foo');
            $table->string('remove_this');
            $table->removeColumn('remove_this');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['alter table `users` add `foo` varchar(255) not null'], $blueprint->toSql($connection, new MySqlGrammar));
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
        $blueprint = new Blueprint('comments', function ($table) {
            $table->morphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null, add `commentable_id` bigint unsigned not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingNullableIdMorph()
    {
        $blueprint = new Blueprint('comments', function ($table) {
            $table->nullableMorphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null, add `commentable_id` bigint unsigned null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingUuidMorph()
    {
        Builder::defaultMorphKeyType('uuid');

        $blueprint = new Blueprint('comments', function ($table) {
            $table->morphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) not null, add `commentable_id` char(36) not null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDefaultUsingNullableUuidMorph()
    {
        Builder::defaultMorphKeyType('uuid');

        $blueprint = new Blueprint('comments', function ($table) {
            $table->nullableMorphs('commentable');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `comments` add `commentable_type` varchar(255) null, add `commentable_id` char(36) null',
            'alter table `comments` add index `comments_commentable_type_commentable_id_index`(`commentable_type`, `commentable_id`)',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithIncrementalModel()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->foreignIdFor('Illuminate\Foundation\Auth\User');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` add `user_id` bigint unsigned not null',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testGenerateRelationshipColumnWithUuidModel()
    {
        require_once __DIR__.'/../stubs/EloquentModelUuidStub.php';

        $blueprint = new Blueprint('posts', function ($table) {
            $table->foreignIdFor('EloquentModelUuidStub');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` add `eloquent_model_uuid_stub_id` char(36) not null',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropRelationshipColumnWithIncrementalModel()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->dropForeignIdFor('Illuminate\Foundation\Auth\User');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_user_id_foreign`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testTinyTextNullableColumn()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->tinyText('note')->nullable();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` add `note` tinytext null',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropRelationshipColumnWithUuidModel()
    {
        require_once __DIR__.'/../stubs/EloquentModelUuidStub.php';

        $blueprint = new Blueprint('posts', function ($table) {
            $table->dropForeignIdFor('EloquentModelUuidStub');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_eloquent_model_uuid_stub_id_foreign`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropConstrainedRelationshipColumnWithIncrementalModel()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->dropConstrainedForeignIdFor('Illuminate\Foundation\Auth\User');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_user_id_foreign`',
            'alter table `posts` drop `user_id`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testDropConstrainedRelationshipColumnWithUuidModel()
    {
        require_once __DIR__.'/../stubs/EloquentModelUuidStub.php';

        $blueprint = new Blueprint('posts', function ($table) {
            $table->dropConstrainedForeignIdFor('EloquentModelUuidStub');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` drop foreign key `posts_eloquent_model_uuid_stub_id_foreign`',
            'alter table `posts` drop `eloquent_model_uuid_stub_id`',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testTableComment()
    {
        $blueprint = new Blueprint('posts', function (Blueprint $table) {
            $table->comment('Look at my comment, it is amazing');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table `posts` comment = \'Look at my comment, it is amazing\'',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }
}
