<?php

use Mockery as m;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSQLiteSchemaGrammarTest extends PHPUnit_Framework_TestCase {

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
		$this->assertEquals('create table "users" ("id" integer null primary key autoincrement, "email" varchar null)', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$blueprint->string('email');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(2, count($statements));
		$expected = array(
			'alter table "users" add column "id" integer null primary key autoincrement',
			'alter table "users" add column "email" varchar null',
		);
		$this->assertEquals($expected, $statements);
	}


	public function testDropTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->drop();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop table "users"', $statements[0]);
	}


	public function testDropTableIfExists()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropIfExists();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop table if exists "users"', $statements[0]);
	}


	/**
	 * @expectedException BadMethodCallException
	 */
	public function testDropColumn()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropColumn('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
	}


	public function testDropUnique()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropUnique('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop index foo', $statements[0]);
	}


	public function testDropIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropIndex('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop index foo', $statements[0]);
	}


	public function testRenameTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->rename('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" rename to "foo"', $statements[0]);
	}


	public function testAddingPrimaryKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->create();
		$blueprint->string('foo')->primary();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create table "users" ("foo" varchar null, primary key ("foo"))', $statements[0]);
	}


	public function testAddingForeignKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->create();
		$blueprint->string('foo')->primary();
		$blueprint->string('order_id');
		$blueprint->foreign('order_id')->references('id')->on('orders');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create table "users" ("foo" varchar null, "order_id" varchar null, foreign key("order_id") references "orders"("id"), primary key ("foo"))', $statements[0]);
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
		$blueprint->index(array('foo', 'bar'), 'baz');
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
		$this->assertEquals('alter table "users" add column "id" integer null primary key autoincrement', $statements[0]);
	}


	public function testAddingString()
	{
		$blueprint = new Blueprint('users');
		$blueprint->string('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100)->nullable()->default('bar');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar null default \'bar\'', $statements[0]);
	}


	public function testAddingText()
	{
		$blueprint = new Blueprint('users');
		$blueprint->text('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" text null', $statements[0]);
	}


	public function testAddingInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->integer('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" integer null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->integer('foo', true);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" integer null primary key autoincrement', $statements[0]);				
	}


	public function testAddingTinyInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->tinyInteger('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" integer null', $statements[0]);
	}


	public function testAddingFloat()
	{
		$blueprint = new Blueprint('users');
		$blueprint->float('foo', 5, 2);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" float null', $statements[0]);		
	}


	public function testAddingDecimal()
	{
		$blueprint = new Blueprint('users');
		$blueprint->decimal('foo', 5, 2);
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" float null', $statements[0]);
	}


	public function testAddingBoolean()
	{
		$blueprint = new Blueprint('users');
		$blueprint->boolean('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" tinyint null', $statements[0]);
	}


	public function testAddingEnum()
	{
		$blueprint = new Blueprint('users');
		$blueprint->enum('foo', array('bar', 'baz'));
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar null', $statements[0]);
	}


	public function testAddingDate()
	{
		$blueprint = new Blueprint('users');
		$blueprint->date('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" date null', $statements[0]);
	}


	public function testAddingDateTime()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dateTime('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" datetime null', $statements[0]);
	}


	public function testAddingTime()
	{
		$blueprint = new Blueprint('users');
		$blueprint->time('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" time null', $statements[0]);
	}


	public function testAddingTimeStamp()
	{
		$blueprint = new Blueprint('users');
		$blueprint->timestamp('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" datetime null', $statements[0]);
	}


	public function testAddingTimeStamps()
	{
		$blueprint = new Blueprint('users');
		$blueprint->timestamps();
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(2, count($statements));
		$expected = array(
			'alter table "users" add column "created_at" datetime null',
			'alter table "users" add column "updated_at" datetime null'
		);
		$this->assertEquals($expected, $statements);
	}


	public function testAddingBinary()
	{
		$blueprint = new Blueprint('users');
		$blueprint->binary('foo');
		$statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" blob null', $statements[0]);
	}


	protected function getConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}


	public function getGrammar()
	{
		return new Illuminate\Database\Schema\Grammars\SQLiteGrammar;
	}

}