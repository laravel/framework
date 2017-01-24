<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;

class DatabaseMySqlSchemaGrammarTest extends TestCase
{
    public function tearDown()
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

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8 collate utf8_unicode_ci', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `id` int unsigned not null auto_increment primary key, add `email` varchar(255) not null', $statements[0]);
    }

    public function testEngineCreateTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $blueprint->engine = 'InnoDB';

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8 collate utf8_unicode_ci engine = InnoDB', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn('InnoDB');

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8 collate utf8_unicode_ci engine = InnoDB', $statements[0]);
    }

    public function testCharsetCollationCreateTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $blueprint->charset = 'utf8mb4';
        $blueprint->collation = 'utf8mb4_unicode_ci';

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null) default character set utf8mb4 collate utf8mb4_unicode_ci', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email')->charset('utf8mb4')->collation('utf8mb4_unicode_ci');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $conn->shouldReceive('getConfig')->once()->with('collation')->andReturn('utf8_unicode_ci');
        $conn->shouldReceive('getConfig')->once()->with('engine')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create table `users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) character set utf8mb4 collate utf8mb4_unicode_ci not null) default character set utf8 collate utf8_unicode_ci', $statements[0]);
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

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create table `prefix_users` (`id` int unsigned not null auto_increment primary key, `email` varchar(255) not null)', $statements[0]);
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop table `users`', $statements[0]);
    }

    public function testDropTableIfExists()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop table if exists `users`', $statements[0]);
    }

    public function testDropColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop `foo`', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop `foo`, drop `bar`', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop `foo`, drop `bar`', $statements[0]);
    }

    public function testDropPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropPrimary();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop primary key', $statements[0]);
    }

    public function testDropUnique()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop index `foo`', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop index `foo`', $statements[0]);
    }

    public function testDropForeign()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop foreign key `foo`', $statements[0]);
    }

    public function testDropTimestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop `created_at`, drop `updated_at`', $statements[0]);
    }

    public function testDropTimestampsTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` drop `created_at`, drop `updated_at`', $statements[0]);
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('rename table `users` to `foo`', $statements[0]);
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add primary key `bar`(`foo`)', $statements[0]);
    }

    public function testAddingPrimaryKeyWithAlgorithm()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo', 'bar', 'hash');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add primary key `bar` using hash(`foo`)', $statements[0]);
    }

    public function testAddingUniqueKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add unique `bar`(`foo`)', $statements[0]);
    }

    public function testAddingIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add index `baz`(`foo`, `bar`)', $statements[0]);
    }

    public function testAddingIndexWithAlgorithm()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz', 'hash');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add index `baz` using hash(`foo`, `bar`)', $statements[0]);
    }

    public function testAddingForeignKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->foreign('foo_id')->references('id')->on('orders');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add constraint `users_foo_id_foreign` foreign key (`foo_id`) references `orders` (`id`)', $statements[0]);
    }

    public function testAddingIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `id` int unsigned not null auto_increment primary key', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `id` smallint unsigned not null auto_increment primary key', $statements[0]);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `id` bigint unsigned not null auto_increment primary key', $statements[0]);
    }

    public function testAddingColumnInTableFirst()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('name')->first();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `name` varchar(255) not null first', $statements[0]);
    }

    public function testAddingColumnAfterAnotherColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('name')->after('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `name` varchar(255) not null after `foo`', $statements[0]);
    }

    public function testAddingGeneratedColumn()
    {
        $blueprint = new Blueprint('products');
        $blueprint->integer('price');
        $blueprint->integer('discounted_virtual')->virtualAs('price - 5');
        $blueprint->integer('discounted_stored')->storedAs('price - 5');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `products` add `price` int not null, add `discounted_virtual` int as (price - 5), add `discounted_stored` int as (price - 5) stored', $statements[0]);
    }

    public function testAddingString()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` varchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` varchar(100) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` varchar(100) null default \'bar\'', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default(new \Illuminate\Database\Query\Expression('CURRENT TIMESTAMP'));
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` varchar(100) null default CURRENT TIMESTAMP', $statements[0]);
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint('users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` text not null', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` bigint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` bigint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` int not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` int not null auto_increment primary key', $statements[0]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` mediumint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` mediumint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` smallint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` smallint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` tinyint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` tinyint not null auto_increment primary key', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint('users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` double(5, 2) not null', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` double not null', $statements[0]);
    }

    public function testAddingDoubleSpecifyingPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo', 15, 8);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` double(15, 8) not null', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint('users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` decimal(5, 2) not null', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` tinyint(1) not null', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('foo', ['bar', 'baz']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` enum(\'bar\', \'baz\') not null', $statements[0]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` json not null', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` json not null', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint('users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` date not null', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` datetime not null', $statements[0]);
    }

    public function testAddingDateTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` datetime not null', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` time not null', $statements[0]);
    }

    public function testAddingTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` time not null', $statements[0]);
    }

    public function testAddingTimeStamp()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` timestamp not null', $statements[0]);
    }

    public function testAddingTimeStampWithDefault()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('foo')->default('2015-07-22 11:43:17');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` timestamp not null default \'2015-07-22 11:43:17\'', $statements[0]);
    }

    public function testAddingTimeStampTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` timestamp not null', $statements[0]);
    }

    public function testAddingTimeStampTzWithDefault()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('foo')->default('2015-07-22 11:43:17');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` timestamp not null default \'2015-07-22 11:43:17\'', $statements[0]);
    }

    public function testAddingTimeStamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `created_at` timestamp null, add `updated_at` timestamp null', $statements[0]);
    }

    public function testAddingTimeStampsTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `created_at` timestamp null, add `updated_at` timestamp null', $statements[0]);
    }

    public function testAddingRememberToken()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rememberToken();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `remember_token` varchar(100) null', $statements[0]);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` blob not null', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` char(36) not null', $statements[0]);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` varchar(45) not null', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table `users` add `foo` varchar(17) not null', $statements[0]);
    }

    protected function getConnection()
    {
        return m::mock('Illuminate\Database\Connection');
    }

    public function getGrammar()
    {
        return new \Illuminate\Database\Schema\Grammars\MySqlGrammar;
    }
}
