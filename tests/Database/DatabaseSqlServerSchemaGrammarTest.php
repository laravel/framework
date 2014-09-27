<?php

use Mockery as m;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSqlServerSchemaGrammarTest extends PHPUnit_Framework_TestCase {

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
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create table "users" ("id" int identity primary key not null, "email" nvarchar(255) not null)', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$blueprint->string('email');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "id" int identity primary key not null, "email" nvarchar(255) not null', $statements[0]);
	}


	public function testDropTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->drop();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop table "users"', $statements[0]);
	}


	public function testDropColumn()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropColumn('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop column "foo"', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->dropColumn(['foo', 'bar']);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop column "foo", "bar"', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->dropColumn('foo', 'bar');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop column "foo", "bar"', $statements[0]);
	}


	public function testDropPrimary()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropPrimary('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop constraint foo', $statements[0]);
	}


	public function testDropUnique()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropUnique('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop index foo on "users"', $statements[0]);
	}


	public function testDropIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropIndex('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop index foo on "users"', $statements[0]);
	}


	public function testDropForeign()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropForeign('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop constraint foo', $statements[0]);
	}


	public function testDropTimestamps()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropTimestamps();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop column "created_at", "updated_at"', $statements[0]);
	}


	public function testRenameTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->rename('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('sp_rename "users", "foo"', $statements[0]);
	}


	public function testAddingPrimaryKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->primary('foo', 'bar');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add constraint bar primary key ("foo")', $statements[0]);
	}


	public function testAddingUniqueKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->unique('foo', 'bar');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create unique index bar on "users" ("foo")', $statements[0]);
	}


	public function testAddingIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->index(['foo', 'bar'], 'baz');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create index baz on "users" ("foo", "bar")', $statements[0]);
	}


	public function testAddingIncrementingID()
	{
		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "id" int identity primary key not null', $statements[0]);
	}


	public function testAddingBigIncrementingID()
	{
		$blueprint = new Blueprint('users');
		$blueprint->bigIncrements('id');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "id" bigint identity primary key not null', $statements[0]);
	}


	public function testAddingString()
	{
		$blueprint = new Blueprint('users');
		$blueprint->string('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" nvarchar(255) not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" nvarchar(100) not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100)->nullable()->default('bar');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" nvarchar(100) null default \'bar\'', $statements[0]);
	}


	public function testAddingText()
	{
		$blueprint = new Blueprint('users');
		$blueprint->text('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
	}


	public function testAddingBigInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->bigInteger('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" bigint not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->bigInteger('foo', true);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" bigint identity primary key not null', $statements[0]);
	}


	public function testAddingInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->integer('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" int not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->integer('foo', true);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" int identity primary key not null', $statements[0]);
	}


	public function testAddingMediumInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->mediumInteger('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" int not null', $statements[0]);
	}


	public function testAddingTinyInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->tinyInteger('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" tinyint not null', $statements[0]);
	}


	public function testAddingSmallInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->smallInteger('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" smallint not null', $statements[0]);
	}


	public function testAddingFloat()
	{
		$blueprint = new Blueprint('users');
		$blueprint->float('foo', 5, 2);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" float not null', $statements[0]);
	}


	public function testAddingDouble()
	{
		$blueprint = new Blueprint('users');
		$blueprint->double('foo', 15, 2);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" float not null', $statements[0]);
	}


	public function testAddingDecimal()
	{
		$blueprint = new Blueprint('users');
		$blueprint->decimal('foo', 5, 2);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" decimal(5, 2) not null', $statements[0]);
	}


	public function testAddingBoolean()
	{
		$blueprint = new Blueprint('users');
		$blueprint->boolean('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" bit not null', $statements[0]);
	}


	public function testAddingEnum()
	{
		$blueprint = new Blueprint('users');
		$blueprint->enum('foo', ['bar', 'baz']);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" nvarchar(255) not null', $statements[0]);
	}


	public function testAddingDate()
	{
		$blueprint = new Blueprint('users');
		$blueprint->date('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" date not null', $statements[0]);
	}


	public function testAddingDateTime()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dateTime('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" datetime not null', $statements[0]);
	}


	public function testAddingTime()
	{
		$blueprint = new Blueprint('users');
		$blueprint->time('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" time not null', $statements[0]);
	}


	public function testAddingTimeStamp()
	{
		$blueprint = new Blueprint('users');
		$blueprint->timestamp('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" datetime not null', $statements[0]);
	}


	public function testAddingTimeStamps()
	{
		$blueprint = new Blueprint('users');
		$blueprint->timestamps();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "created_at" datetime not null, "updated_at" datetime not null', $statements[0]);
	}


	public function testAddingRememberToken()
	{
		$blueprint = new Blueprint('users');
		$blueprint->rememberToken();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "remember_token" nvarchar(100) null', $statements[0]);
	}


	public function testAddingBinary()
	{
		$blueprint = new Blueprint('users');
		$blueprint->binary('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add "foo" varbinary(max) not null', $statements[0]);
	}


	protected function getConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}


	public function getGrammar()
	{
		return new Illuminate\Database\Schema\Grammars\SqlServerGrammar;
	}

}
