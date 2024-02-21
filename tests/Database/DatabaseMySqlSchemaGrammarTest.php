<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Tests\Database\Fixtures\Enums\Foo;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMySqlSchemaGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8 collate 'utf8_unicode_ci'", $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `id` int unsigned not null auto_increment primary key, add `email` varchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->uuid('id')->primary();

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table `users` (`id` char(36) not null, primary key (`id`))', $statements[0]);
    }

    public function testAutoIncrementStartingValue()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id')->startingValue(1000);
        $blueprint->string('email');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame("create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8 collate 'utf8_unicode_ci'", $statements[0]);
        $this->assertSame('alter table `users` auto_increment = 1000', $statements[1]);
    }

    public function testAddColumnsWithMultipleAutoIncrementStartingValue()
    {
        $blueprint = new Blueprint('users');
        $blueprint->id()->from(100);
        $blueprint->string('name')->from(200);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals([
            'alter table `users` add `id` bigint unsigned not null auto_increment primary key, add `name` varchar(255) not null',
            'alter table `users` auto_increment = 100',
        ], $statements);
    }

    public function testEngineCreateTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $blueprint->engine('InnoDB');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8 collate 'utf8_unicode_ci' engine = InnoDB", $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn('InnoDB');

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8 collate 'utf8_unicode_ci' engine = InnoDB", $statements[0]);
    }

    public function testCharsetCollationCreateTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $blueprint->charset('utf8mb4');
        $blueprint->collation('utf8mb4_unicode_ci');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'", $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email')->charset('utf8mb4')->collation('utf8mb4_unicode_ci');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) character set utf8mb4 collate 'utf8mb4_unicode_ci' not null) default character set utf8 collate 'utf8_unicode_ci'", $statements[0]);
    }

    public function testBasicCreateTableWithPrefix()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertCount(1, $statements);
        $this->assertSame('create table `prefix_users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null)', $statements[0]);
    }

    public function testCreateTemporaryTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->temporary();
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create temporary table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null)', $statements[0]);
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop table `users`', $statements[0]);
    }

    public function testDropTableIfExists()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop table if exists `users`', $statements[0]);
    }

    public function testDropColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop `foo`', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop `foo`, drop `bar`', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop `foo`, drop `bar`', $statements[0]);
    }

    public function testDropPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropPrimary();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop primary key', $statements[0]);
    }

    public function testDropUnique()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop index `foo`', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop index `foo`', $statements[0]);
    }

    public function testDropSpatialIndex()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` drop index `geo_coordinates_spatialindex`', $statements[0]);
    }

    public function testDropForeign()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop foreign key `foo`', $statements[0]);
    }

    public function testDropTimestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop `created_at`, drop `updated_at`', $statements[0]);
    }

    public function testDropTimestampsTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` drop `created_at`, drop `updated_at`', $statements[0]);
    }

    public function testDropMorphs()
    {
        $blueprint = new Blueprint('photos');
        $blueprint->dropMorphs('imageable');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('alter table `photos` drop index `photos_imageable_type_imageable_id_index`', $statements[0]);
        $this->assertSame('alter table `photos` drop `imageable_type`, drop `imageable_id`', $statements[1]);
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('rename table `users` to `foo`', $statements[0]);
    }

    public function testRenameIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->renameIndex('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` rename index `foo` to `bar`', $statements[0]);
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add primary key (`foo`)', $statements[0]);
    }

    public function testAddingPrimaryKeyWithAlgorithm()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo', 'bar', 'hash');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add primary key using hash(`foo`)', $statements[0]);
    }

    public function testAddingUniqueKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add unique `bar`(`foo`)', $statements[0]);
    }

    public function testAddingIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add index `baz`(`foo`, `bar`)', $statements[0]);
    }

    public function testAddingIndexWithAlgorithm()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz', 'hash');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add index `baz` using hash(`foo`, `bar`)', $statements[0]);
    }

    public function testAddingFulltextIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->fulltext('body');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add fulltext `users_body_fulltext`(`body`)', $statements[0]);
    }

    public function testAddingSpatialIndex()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add spatial index `geo_coordinates_spatialindex`(`coordinates`)', $statements[0]);
    }

    public function testAddingFluentSpatialIndex()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates')->spatialIndex();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('alter table `geo` add spatial index `geo_coordinates_spatialindex`(`coordinates`)', $statements[1]);
    }

    public function testAddingRawIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rawIndex('(function(column))', 'raw_index');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add index `raw_index`((function(column)))', $statements[0]);
    }

    public function testAddingForeignKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->foreign('foo_id')->references('id')->on('orders');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add constraint `users_foo_id_foreign` foreign key (`foo_id`) references `orders` (`id`)', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->foreign('foo_id')->references('id')->on('orders')->cascadeOnDelete();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add constraint `users_foo_id_foreign` foreign key (`foo_id`) references `orders` (`id`) on delete cascade', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->foreign('foo_id')->references('id')->on('orders')->cascadeOnUpdate();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add constraint `users_foo_id_foreign` foreign key (`foo_id`) references `orders` (`id`) on update cascade', $statements[0]);
    }

    public function testAddingIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `id` int unsigned not null auto_increment primary key', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `id` smallint unsigned not null auto_increment primary key', $statements[0]);
    }

    public function testAddingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `id` bigint unsigned not null auto_increment primary key', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->id('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` bigint unsigned not null auto_increment primary key', $statements[0]);
    }

    public function testAddingForeignID()
    {
        $blueprint = new Blueprint('users');
        $foreignId = $blueprint->foreignId('foo');
        $blueprint->foreignId('company_id')->constrained();
        $blueprint->foreignId('laravel_idea_id')->constrained();
        $blueprint->foreignId('team_id')->references('id')->on('teams');
        $blueprint->foreignId('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignId);
        $this->assertSame([
            'alter table `users` add `foo` bigint unsigned not null, add `company_id` bigint unsigned not null, add `laravel_idea_id` bigint unsigned not null, add `team_id` bigint unsigned not null, add `team_column_id` bigint unsigned not null',
            'alter table `users` add constraint `users_company_id_foreign` foreign key (`company_id`) references `companies` (`id`)',
            'alter table `users` add constraint `users_laravel_idea_id_foreign` foreign key (`laravel_idea_id`) references `laravel_ideas` (`id`)',
            'alter table `users` add constraint `users_team_id_foreign` foreign key (`team_id`) references `teams` (`id`)',
            'alter table `users` add constraint `users_team_column_id_foreign` foreign key (`team_column_id`) references `teams` (`id`)',
        ], $statements);
    }

    public function testAddingForeignIdSpecifyingIndexNameInConstraint()
    {
        $blueprint = new Blueprint('users');
        $blueprint->foreignId('company_id')->constrained(indexName: 'my_index');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertSame([
            'alter table `users` add `company_id` bigint unsigned not null',
            'alter table `users` add constraint `my_index` foreign key (`company_id`) references `companies` (`id`)',
        ], $statements);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `id` bigint unsigned not null auto_increment primary key', $statements[0]);
    }

    public function testAddingColumnInTableFirst()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('name')->first();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `name` varchar(255) not null first', $statements[0]);
    }

    public function testAddingColumnAfterAnotherColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('name')->after('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `name` varchar(255) not null after `foo`', $statements[0]);
    }

    public function testAddingMultipleColumnsAfterAnotherColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->after('foo', function ($blueprint) {
            $blueprint->string('one');
            $blueprint->string('two');
        });
        $blueprint->string('three');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `one` varchar(255) not null after `foo`, add `two` varchar(255) not null after `one`, add `three` varchar(255) not null', $statements[0]);
    }

    public function testAddingGeneratedColumn()
    {
        $blueprint = new Blueprint('products');
        $blueprint->integer('price');
        $blueprint->integer('discounted_virtual')->virtualAs('price - 5');
        $blueprint->integer('discounted_stored')->storedAs('price - 5');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `products` add `price` int not null, add `discounted_virtual` int as (price - 5), add `discounted_stored` int as (price - 5) stored', $statements[0]);

        $blueprint = new Blueprint('products');
        $blueprint->integer('price');
        $blueprint->integer('discounted_virtual')->virtualAs('price - 5')->nullable(false);
        $blueprint->integer('discounted_stored')->storedAs('price - 5')->nullable(false);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `products` add `price` int not null, add `discounted_virtual` int as (price - 5) not null, add `discounted_stored` int as (price - 5) stored not null', $statements[0]);
    }

    public function testAddingGeneratedColumnWithCharset()
    {
        $blueprint = new Blueprint('links');
        $blueprint->string('url', 2083)->charset('ascii');
        $blueprint->string('url_hash_virtual', 64)->virtualAs('sha2(url, 256)')->charset('ascii');
        $blueprint->string('url_hash_stored', 64)->storedAs('sha2(url, 256)')->charset('ascii');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `links` add `url` varchar(2083) character set ascii not null, add `url_hash_virtual` varchar(64) character set ascii as (sha2(url, 256)), add `url_hash_stored` varchar(64) character set ascii as (sha2(url, 256)) stored', $statements[0]);
    }

    public function testAddingGeneratedColumnByExpression()
    {
        $blueprint = new Blueprint('products');
        $blueprint->integer('price');
        $blueprint->integer('discounted_virtual')->virtualAs(new Expression('price - 5'));
        $blueprint->integer('discounted_stored')->storedAs(new Expression('price - 5'));
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `products` add `price` int not null, add `discounted_virtual` int as (price - 5), add `discounted_stored` int as (price - 5) stored', $statements[0]);
    }

    public function testAddingInvisibleColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('secret', 64)->nullable(false)->invisible();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `secret` varchar(64) not null invisible', $statements[0]);
    }

    public function testAddingString()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` varchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` varchar(100) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` varchar(100) null default \'bar\'', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default(new Expression('CURRENT TIMESTAMP'));
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` varchar(100) null default CURRENT TIMESTAMP', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default(Foo::BAR);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` varchar(100) null default \'bar\'', $statements[0]);
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint('users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` text not null', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` bigint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` bigint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` int not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` int not null auto_increment primary key', $statements[0]);
    }

    public function testAddingIncrementsWithStartingValues()
    {
        $blueprint = new Blueprint('users');
        $blueprint->id()->startingValue(1000);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('alter table `users` add `id` bigint unsigned not null auto_increment primary key', $statements[0]);
        $this->assertSame('alter table `users` auto_increment = 1000', $statements[1]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` mediumint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` mediumint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` smallint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` smallint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` tinyint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` tinyint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint('users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` double(5, 2) not null', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` double not null', $statements[0]);
    }

    public function testAddingDoubleSpecifyingPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo', 15, 8);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` double(15, 8) not null', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint('users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` decimal(5, 2) not null', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` tinyint(1) not null', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('role', ['member', 'admin']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `role` enum(\'member\', \'admin\') not null', $statements[0]);
    }

    public function testAddingSet()
    {
        $blueprint = new Blueprint('users');
        $blueprint->set('role', ['member', 'admin']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `role` set(\'member\', \'admin\') not null', $statements[0]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` json not null', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` json not null', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint('users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` date not null', $statements[0]);
    }

    public function testAddingYear()
    {
        $blueprint = new Blueprint('users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `birth_year` year not null', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime(1) not null', $statements[0]);
    }

    public function testAddingDateTimeWithDefaultCurrent()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo')->useCurrent();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime not null default CURRENT_TIMESTAMP', $statements[0]);
    }

    public function testAddingDateTimeWithOnUpdateCurrent()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo')->useCurrentOnUpdate();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime not null on update CURRENT_TIMESTAMP', $statements[0]);
    }

    public function testAddingDateTimeWithDefaultCurrentAndOnUpdateCurrent()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo')->useCurrent()->useCurrentOnUpdate();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP', $statements[0]);
    }

    public function testAddingDateTimeWithDefaultCurrentOnUpdateCurrentAndPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo', 3)->useCurrent()->useCurrentOnUpdate();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime(3) not null default CURRENT_TIMESTAMP(3) on update CURRENT_TIMESTAMP(3)', $statements[0]);
    }

    public function testAddingDateTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('foo', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime(1) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` datetime not null', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` time not null', $statements[0]);
    }

    public function testAddingTimeWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` time(1) not null', $statements[0]);
    }

    public function testAddingTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` time not null', $statements[0]);
    }

    public function testAddingTimeTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` time(1) not null', $statements[0]);
    }

    public function testAddingTimestamp()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp not null', $statements[0]);
    }

    public function testAddingTimestampWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp(1) not null', $statements[0]);
    }

    public function testAddingTimestampWithDefault()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at')->default('2015-07-22 11:43:17');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame("alter table `users` add `created_at` timestamp not null default '2015-07-22 11:43:17'", $statements[0]);
    }

    public function testAddingTimestampWithDefaultCurrentSpecifyingPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1)->useCurrent();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp(1) not null default CURRENT_TIMESTAMP(1)', $statements[0]);
    }

    public function testAddingTimestampWithOnUpdateCurrentSpecifyingPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1)->useCurrentOnUpdate();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp(1) not null on update CURRENT_TIMESTAMP(1)', $statements[0]);
    }

    public function testAddingTimestampWithDefaultCurrentAndOnUpdateCurrentSpecifyingPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1)->useCurrent()->useCurrentOnUpdate();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp(1) not null default CURRENT_TIMESTAMP(1) on update CURRENT_TIMESTAMP(1)', $statements[0]);
    }

    public function testAddingTimestampTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp not null', $statements[0]);
    }

    public function testAddingTimestampTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp(1) not null', $statements[0]);
    }

    public function testAddingTimeStampTzWithDefault()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at')->default('2015-07-22 11:43:17');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame("alter table `users` add `created_at` timestamp not null default '2015-07-22 11:43:17'", $statements[0]);
    }

    public function testAddingTimestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp null, add `updated_at` timestamp null', $statements[0]);
    }

    public function testAddingTimestampsTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `created_at` timestamp null, add `updated_at` timestamp null', $statements[0]);
    }

    public function testAddingRememberToken()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rememberToken();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `remember_token` varchar(100) null', $statements[0]);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` blob not null', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` char(36) not null', $statements[0]);
    }

    public function testAddingUuidDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `uuid` char(36) not null', $statements[0]);
    }

    public function testAddingForeignUuid()
    {
        $blueprint = new Blueprint('users');
        $foreignUuid = $blueprint->foreignUuid('foo');
        $blueprint->foreignUuid('company_id')->constrained();
        $blueprint->foreignUuid('laravel_idea_id')->constrained();
        $blueprint->foreignUuid('team_id')->references('id')->on('teams');
        $blueprint->foreignUuid('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignUuid);
        $this->assertSame([
            'alter table `users` add `foo` char(36) not null, add `company_id` char(36) not null, add `laravel_idea_id` char(36) not null, add `team_id` char(36) not null, add `team_column_id` char(36) not null',
            'alter table `users` add constraint `users_company_id_foreign` foreign key (`company_id`) references `companies` (`id`)',
            'alter table `users` add constraint `users_laravel_idea_id_foreign` foreign key (`laravel_idea_id`) references `laravel_ideas` (`id`)',
            'alter table `users` add constraint `users_team_id_foreign` foreign key (`team_id`) references `teams` (`id`)',
            'alter table `users` add constraint `users_team_column_id_foreign` foreign key (`team_column_id`) references `teams` (`id`)',
        ], $statements);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` varchar(45) not null', $statements[0]);
    }

    public function testAddingIpAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `ip_address` varchar(45) not null', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `foo` varchar(17) not null', $statements[0]);
    }

    public function testAddingMacAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `mac_address` varchar(17) not null', $statements[0]);
    }

    public function testAddingGeometry()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` geometry not null', $statements[0]);
    }

    public function testAddingPoint()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` point not null', $statements[0]);
    }

    public function testAddingPointWithSrid()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates', 4326);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` point not null srid 4326', $statements[0]);
    }

    public function testAddingPointWithSridColumn()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates', 4326)->after('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` point not null srid 4326 after `id`', $statements[0]);
    }

    public function testAddingLineString()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->linestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` linestring not null', $statements[0]);
    }

    public function testAddingPolygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->polygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` polygon not null', $statements[0]);
    }

    public function testAddingGeometryCollection()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometrycollection('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` geometrycollection not null', $statements[0]);
    }

    public function testAddingMultiPoint()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipoint('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` multipoint not null', $statements[0]);
    }

    public function testAddingMultiLineString()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multilinestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` multilinestring not null', $statements[0]);
    }

    public function testAddingMultiPolygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipolygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table `geo` add `coordinates` multipolygon not null', $statements[0]);
    }

    public function testAddingComment()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo')->comment("Escape ' when using words like it's");
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("alter table `users` add `foo` varchar(255) not null comment 'Escape \\' when using words like it\\'s'", $statements[0]);
    }

    public function testCreateDatabase()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8mb4_foo');
        $connection->shouldReceive('getConfig')->once()->once()->with('collation')->andReturn('utf8mb4_unicode_ci_foo');

        $statement = $this->getGrammar()->compileCreateDatabase('my_database_a', $connection);

        $this->assertSame(
            'create database `my_database_a` default character set `utf8mb4_foo` default collate `utf8mb4_unicode_ci_foo`',
            $statement
        );

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8mb4_bar');
        $connection->shouldReceive('getConfig')->once()->once()->with('collation')->andReturn('utf8mb4_unicode_ci_bar');

        $statement = $this->getGrammar()->compileCreateDatabase('my_database_b', $connection);

        $this->assertSame(
            'create database `my_database_b` default character set `utf8mb4_bar` default collate `utf8mb4_unicode_ci_bar`',
            $statement
        );
    }

    public function testCreateTableWithVirtualAsColumn()
    {
        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_column');
        $blueprint->string('my_other_column')->virtualAs('my_column');

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`my_column` varchar(255) not null, `my_other_column` varchar(255) as (my_column)) default character set utf8 collate 'utf8_unicode_ci'", $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->virtualAsJson('my_json_column->some_attribute');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`my_json_column` varchar(255) not null, `my_other_column` varchar(255) as (json_unquote(json_extract(`my_json_column`, '$.\"some_attribute\"'))))", $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->virtualAsJson('my_json_column->some_attribute->nested');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`my_json_column` varchar(255) not null, `my_other_column` varchar(255) as (json_unquote(json_extract(`my_json_column`, '$.\"some_attribute\".\"nested\"'))))", $statements[0]);
    }

    public function testCreateTableWithVirtualAsColumnWhenJsonColumnHasArrayKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column')->virtualAsJson('my_json_column->foo[0][1]');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`my_json_column` varchar(255) as (json_unquote(json_extract(`my_json_column`, '$.\"foo\"[0][1]'))))", $statements[0]);
    }

    public function testCreateTableWithStoredAsColumn()
    {
        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_column');
        $blueprint->string('my_other_column')->storedAs('my_column');

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`my_column` varchar(255) not null, `my_other_column` varchar(255) as (my_column) stored) default character set utf8 collate 'utf8_unicode_ci'", $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->storedAsJson('my_json_column->some_attribute');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`my_json_column` varchar(255) not null, `my_other_column` varchar(255) as (json_unquote(json_extract(`my_json_column`, '$.\"some_attribute\"'))) stored)", $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->storedAsJson('my_json_column->some_attribute->nested');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table `users` (`my_json_column` varchar(255) not null, `my_other_column` varchar(255) as (json_unquote(json_extract(`my_json_column`, '$.\"some_attribute\".\"nested\"'))) stored)", $statements[0]);
    }

    public function testDropDatabaseIfExists()
    {
        $statement = $this->getGrammar()->compileDropDatabaseIfExists('my_database_a');

        $this->assertSame(
            'drop database if exists `my_database_a`',
            $statement
        );

        $statement = $this->getGrammar()->compileDropDatabaseIfExists('my_database_b');

        $this->assertSame(
            'drop database if exists `my_database_b`',
            $statement
        );
    }

    public function testDropAllTables()
    {
        $statement = $this->getGrammar()->compileDropAllTables(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop table `alpha`,`beta`,`gamma`', $statement);
    }

    public function testDropAllViews()
    {
        $statement = $this->getGrammar()->compileDropAllViews(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop view `alpha`,`beta`,`gamma`', $statement);
    }

    public function testGrammarsAreMacroable()
    {
        // compileReplace macro.
        $this->getGrammar()::macro('compileReplace', function () {
            return true;
        });

        $c = $this->getGrammar()::compileReplace();

        $this->assertTrue($c);
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    public function getGrammar()
    {
        return new MySqlGrammar;
    }
}
