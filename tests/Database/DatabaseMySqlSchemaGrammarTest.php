<?php

use Mockery as m;
use Illuminate\Database\Schema\Blueprint;

class DatabaseMySqlSchemaGrammarTest extends PHPUnit_Framework_TestCase {

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
		$this->assertEquals('alter table `users` drop index foo', $statements[0]);
	}


	public function testDropIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropIndex('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop index foo', $statements[0]);
	}


	public function testDropForeign()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropForeign('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop foreign key foo', $statements[0]);
	}


	public function testDropTimestamps()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropTimestamps();
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
		$this->assertEquals('alter table `users` add primary key bar(`foo`)', $statements[0]);
	}


	public function testAddingUniqueKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->unique('foo', 'bar');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add unique bar(`foo`)', $statements[0]);
	}


	public function testAddingIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->index(['foo', 'bar'], 'baz');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add index baz(`foo`, `bar`)', $statements[0]);
	}


	public function testAddingForeignKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->foreign('foo_id')->references('id')->on('orders');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add constraint users_foo_id_foreign foreign key (`foo_id`) references `orders` (`id`)', $statements[0]);
	}


	public function testAddingIncrementingID()
	{
		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `id` int unsigned not null auto_increment primary key', $statements[0]);
	}


	public function testAddingBigIncrementingID()
	{
		$blueprint = new Blueprint('users');
		$blueprint->bigIncrements('id');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `id` bigint unsigned not null auto_increment primary key', $statements[0]);
	}


	public function testAddingColumnAfterAnotherColumn()
	{
		$blueprint = new Blueprint('users');
		$blueprint->string('name')->after('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `name` varchar(255) not null after `foo`', $statements[0]);
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
		$blueprint->string('foo', 100)->nullable()->default(new Illuminate\Database\Query\Expression('CURRENT TIMESTAMP'));
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
	}


	public function testAddingSmallInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->smallInteger('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `foo` smallint not null', $statements[0]);
	}


	public function testAddingTinyInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->tinyInteger('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `foo` tinyint not null', $statements[0]);
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


	public function testAddingTime()
	{
		$blueprint = new Blueprint('users');
		$blueprint->time('foo');
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
		$this->assertEquals('alter table `users` add `foo` timestamp default 0 not null', $statements[0]);
	}


	public function testAddingTimeStamps()
	{
		$blueprint = new Blueprint('users');
		$blueprint->timestamps();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `created_at` timestamp default 0 not null, add `updated_at` timestamp default 0 not null', $statements[0]);
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


	protected function getConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}


	public function getGrammar()
	{
		return new Illuminate\Database\Schema\Grammars\MySqlGrammar;
	}

}
