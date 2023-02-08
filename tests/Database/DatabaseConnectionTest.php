<?php

namespace Illuminate\Tests\Database;

use DateTime;
use ErrorException;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\MultipleColumnsSelectedException;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Builder;
use Mockery as m;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class DatabaseConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSettingDefaultCallsGetDefaultGrammar()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock(stdClass::class);
        $connection->expects($this->once())->method('getDefaultQueryGrammar')->willReturn($mock);
        $connection->useDefaultQueryGrammar();
        $this->assertEquals($mock, $connection->getQueryGrammar());
    }

    public function testSettingDefaultCallsGetDefaultPostProcessor()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock(stdClass::class);
        $connection->expects($this->once())->method('getDefaultPostProcessor')->willReturn($mock);
        $connection->useDefaultPostProcessor();
        $this->assertEquals($mock, $connection->getPostProcessor());
    }

    public function testSelectOneCallsSelectAndReturnsSingleResult()
    {
        $connection = $this->getMockConnection(['select']);
        $connection->expects($this->once())->method('select')->with('foo', ['bar' => 'baz'])->willReturn(['foo']);
        $this->assertSame('foo', $connection->selectOne('foo', ['bar' => 'baz']));
    }

    public function testScalarCallsSelectOneAndReturnsSingleResult()
    {
        $connection = $this->getMockConnection(['selectOne']);
        $connection->expects($this->once())->method('selectOne')->with('select count(*) from tbl')->willReturn((object) ['count(*)' => 5]);
        $this->assertSame(5, $connection->scalar('select count(*) from tbl'));
    }

    public function testScalarThrowsExceptionIfMultipleColumnsAreSelected()
    {
        $connection = $this->getMockConnection(['selectOne']);
        $connection->expects($this->once())->method('selectOne')->with('select a, b from tbl')->willReturn((object) ['a' => 'a', 'b' => 'b']);
        $this->expectException(MultipleColumnsSelectedException::class);
        $connection->scalar('select a, b from tbl');
    }

    public function testScalarReturnsNullIfUnderlyingSelectReturnsNoRows()
    {
        $connection = $this->getMockConnection(['selectOne']);
        $connection->expects($this->once())->method('selectOne')->with('select foo from tbl where 0=1')->willReturn(null);
        $this->assertNull($connection->scalar('select foo from tbl where 0=1'));
    }

    public function testSelectProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['prepare'])->getMock();
        $writePdo->expects($this->never())->method('prepare');
        $statement = $this->getMockBuilder('PDOStatement')
            ->onlyMethods(['setFetchMode', 'execute', 'fetchAll', 'bindValue'])
            ->getMock();
        $statement->expects($this->once())->method('setFetchMode');
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->willReturn(['boom']);
        $pdo->expects($this->once())->method('prepare')->with('foo')->willReturn($statement);
        $mock = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mock->setReadPdo($pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['foo' => 'bar']))->willReturn(['foo' => 'bar']);
        $results = $mock->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
        $log = $mock->getQueryLog();
        $this->assertSame('foo', $log[0]['query']);
        $this->assertEquals(['foo' => 'bar'], $log[0]['bindings']);
        $this->assertIsNumeric($log[0]['time']);
    }

    public function testInsertCallsTheStatementMethod()
    {
        $connection = $this->getMockConnection(['statement']);
        $connection->expects($this->once())->method('statement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->willReturn('baz');
        $results = $connection->insert('foo', ['bar']);
        $this->assertSame('baz', $results);
    }

    public function testUpdateCallsTheAffectingStatementMethod()
    {
        $connection = $this->getMockConnection(['affectingStatement']);
        $connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->willReturn('baz');
        $results = $connection->update('foo', ['bar']);
        $this->assertSame('baz', $results);
    }

    public function testDeleteCallsTheAffectingStatementMethod()
    {
        $connection = $this->getMockConnection(['affectingStatement']);
        $connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->willReturn(true);
        $results = $connection->delete('foo', ['bar']);
        $this->assertTrue($results);
    }

    public function testStatementProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder('PDOStatement')->onlyMethods(['execute', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with(1, 'bar', 2);
        $statement->expects($this->once())->method('execute')->willReturn(true);
        $pdo->expects($this->once())->method('prepare')->with($this->equalTo('foo'))->willReturn($statement);
        $mock = $this->getMockConnection(['prepareBindings'], $pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['bar']))->willReturn(['bar']);
        $results = $mock->statement('foo', ['bar']);
        $this->assertTrue($results);
        $log = $mock->getQueryLog();
        $this->assertSame('foo', $log[0]['query']);
        $this->assertEquals(['bar'], $log[0]['bindings']);
        $this->assertIsNumeric($log[0]['time']);
    }

    public function testAffectingStatementProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder('PDOStatement')->onlyMethods(['execute', 'rowCount', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('rowCount')->willReturn(42);
        $pdo->expects($this->once())->method('prepare')->with('foo')->willReturn($statement);
        $mock = $this->getMockConnection(['prepareBindings'], $pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['foo' => 'bar']))->willReturn(['foo' => 'bar']);
        $results = $mock->update('foo', ['foo' => 'bar']);
        $this->assertSame(42, $results);
        $log = $mock->getQueryLog();
        $this->assertSame('foo', $log[0]['query']);
        $this->assertEquals(['foo' => 'bar'], $log[0]['bindings']);
        $this->assertIsNumeric($log[0]['time']);
    }

    public function testTransactionLevelNotIncrementedOnTransactionException()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->will($this->throwException(new Exception));
        $connection = $this->getMockConnection([], $pdo);
        try {
            $connection->beginTransaction();
        } catch (Exception $e) {
            $this->assertEquals(0, $connection->transactionLevel());
        }
    }

    public function testBeginTransactionMethodRetriesOnFailure()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->method('beginTransaction')
            ->willReturnOnConsecutiveCalls($this->throwException(new ErrorException('server has gone away')), true);
        $connection = $this->getMockConnection(['reconnect'], $pdo);
        $connection->expects($this->once())->method('reconnect');
        $connection->beginTransaction();
        $this->assertEquals(1, $connection->transactionLevel());
    }

    public function testBeginTransactionMethodReconnectsMissingConnection()
    {
        $connection = $this->getMockConnection();
        $connection->setReconnector(function ($connection) {
            $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
            $connection->setPdo($pdo);
        });
        $connection->disconnect();
        $connection->beginTransaction();
        $this->assertEquals(1, $connection->transactionLevel());
    }

    public function testBeginTransactionMethodNeverRetriesIfWithinTransaction()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('exec')->will($this->throwException(new Exception));
        $connection = $this->getMockConnection(['reconnect'], $pdo);
        $queryGrammar = $this->createMock(Grammar::class);
        $queryGrammar->expects($this->once())->method('compileSavepoint')->willReturn('trans1');
        $queryGrammar->expects($this->once())->method('supportsSavepoints')->willReturn(true);
        $connection->setQueryGrammar($queryGrammar);
        $connection->expects($this->never())->method('reconnect');
        $connection->beginTransaction();
        $this->assertEquals(1, $connection->transactionLevel());
        try {
            $connection->beginTransaction();
        } catch (Exception $e) {
            $this->assertEquals(1, $connection->transactionLevel());
        }
    }

    public function testSwapPDOWithOpenTransactionResetsTransactionLevel()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $connection = $this->getMockConnection([], $pdo);
        $connection->beginTransaction();
        $connection->disconnect();
        $this->assertEquals(0, $connection->transactionLevel());
    }

    public function testBeganTransactionFiresEventsIfSet()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->willReturn('name');
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionBeginning::class));
        $connection->beginTransaction();
    }

    public function testCommittedFiresEventsIfSet()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->willReturn('name');
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionCommitted::class));
        $connection->commit();
    }

    public function testCommittingFiresEventsIfSet()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName', 'transactionLevel'], $pdo);
        $connection->expects($this->any())->method('getName')->willReturn('name');
        $connection->expects($this->any())->method('transactionLevel')->willReturn(1);
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionCommitting::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionCommitted::class));
        $connection->commit();
    }

    public function testRollBackedFiresEventsIfSet()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->willReturn('name');
        $connection->beginTransaction();
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionRolledBack::class));
        $connection->rollBack();
    }

    public function testRedundantRollBackFiresNoEvent()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->willReturn('name');
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldNotReceive('dispatch');
        $connection->rollBack();
    }

    public function testTransactionMethodRunsSuccessfully()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['beginTransaction', 'commit'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('commit');
        $result = $mock->transaction(function ($db) {
            return $db;
        });
        $this->assertEquals($mock, $result);
    }

    public function testTransactionRetriesOnSerializationFailure()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Serialization failure');

        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['beginTransaction', 'commit', 'rollBack'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->exactly(3))->method('commit')->will($this->throwException(new DatabaseConnectionTestMockPDOException('Serialization failure', '40001')));
        $pdo->expects($this->exactly(3))->method('beginTransaction');
        $pdo->expects($this->never())->method('rollBack');
        $mock->transaction(function () {
        }, 3);
    }

    public function testTransactionMethodRetriesOnDeadlock()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Deadlock found when trying to get lock (SQL: )');

        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['inTransaction', 'beginTransaction', 'commit', 'rollBack'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->method('inTransaction')->willReturn(true);
        $pdo->expects($this->exactly(3))->method('beginTransaction');
        $pdo->expects($this->exactly(3))->method('rollBack');
        $pdo->expects($this->never())->method('commit');
        $mock->transaction(function () {
            throw new QueryException('', [], new Exception('Deadlock found when trying to get lock'));
        }, 3);
    }

    public function testTransactionMethodRollsbackAndThrows()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['inTransaction', 'beginTransaction', 'commit', 'rollBack'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        // $pdo->expects($this->once())->method('inTransaction');
        $pdo->method('inTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('rollBack');
        $pdo->expects($this->never())->method('commit');
        try {
            $mock->transaction(function () {
                throw new Exception('foo');
            });
        } catch (Exception $e) {
            $this->assertSame('foo', $e->getMessage());
        }
    }

    public function testOnLostConnectionPDOIsNotSwappedWithinATransaction()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('server has gone away (SQL: foo)');

        $pdo = m::mock(PDO::class);
        $pdo->shouldReceive('beginTransaction')->once();
        $statement = m::mock(PDOStatement::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);
        $statement->shouldReceive('execute')->once()->andThrow(new PDOException('server has gone away'));

        $connection = new Connection($pdo);
        $connection->beginTransaction();
        $connection->statement('foo');
    }

    public function testOnLostConnectionPDOIsSwappedOutsideTransaction()
    {
        $pdo = m::mock(PDO::class);

        $statement = m::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->once()->andThrow(new PDOException('server has gone away'));
        $statement->shouldReceive('execute')->once()->andReturn(true);

        $pdo->shouldReceive('prepare')->twice()->andReturn($statement);

        $connection = new Connection($pdo);

        $called = false;

        $connection->setReconnector(function ($connection) use (&$called) {
            $called = true;
        });

        $this->assertTrue($connection->statement('foo'));

        $this->assertTrue($called);
    }

    public function testRunMethodRetriesOnFailure()
    {
        $method = (new ReflectionClass(Connection::class))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $mock->expects($this->once())->method('tryAgainIfCausedByLostConnection');

        $method->invokeArgs($mock, ['', [], function () {
            throw new QueryException('', [], new Exception);
        }]);
    }

    public function testRunMethodNeverRetriesIfWithinTransaction()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('(SQL: ) (SQL: )');

        $method = (new ReflectionClass(Connection::class))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->onlyMethods(['beginTransaction'])->getMock();
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $mock->expects($this->never())->method('tryAgainIfCausedByLostConnection');
        $mock->beginTransaction();

        $method->invokeArgs($mock, ['', [], function () {
            throw new QueryException('', [], new Exception);
        }]);
    }

    public function testFromCreatesNewQueryBuilder()
    {
        $conn = $this->getMockConnection();
        $conn->setQueryGrammar(m::mock(Grammar::class));
        $conn->setPostProcessor(m::mock(Processor::class));
        $builder = $conn->table('users');
        $this->assertInstanceOf(BaseBuilder::class, $builder);
        $this->assertSame('users', $builder->from);
    }

    public function testPrepareBindings()
    {
        $date = m::mock(DateTime::class);
        $date->shouldReceive('format')->once()->with('foo')->andReturn('bar');
        $bindings = ['test' => $date];
        $conn = $this->getMockConnection();
        $grammar = m::mock(Grammar::class);
        $grammar->shouldReceive('getDateFormat')->once()->andReturn('foo');
        $conn->setQueryGrammar($grammar);
        $result = $conn->prepareBindings($bindings);
        $this->assertEquals(['test' => 'bar'], $result);
    }

    public function testLogQueryFiresEventsIfSet()
    {
        $connection = $this->getMockConnection();
        $connection->logQuery('foo', [], time());
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(QueryExecuted::class));
        $connection->logQuery('foo', [], null);
    }

    public function testBeforeExecutingHooksCanBeRegistered()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The callback was fired');

        $connection = $this->getMockConnection();
        $connection->beforeExecuting(function () {
            throw new Exception('The callback was fired');
        });
        $connection->select('foo bar', ['baz']);
    }

    public function testPretendOnlyLogsQueries()
    {
        $connection = $this->getMockConnection();
        $queries = $connection->pretend(function ($connection) {
            $connection->select('foo bar', ['baz']);
        });
        $this->assertSame('foo bar', $queries[0]['query']);
        $this->assertEquals(['baz'], $queries[0]['bindings']);
    }

    public function testSchemaBuilderCanBeCreated()
    {
        $connection = $this->getMockConnection();
        $schema = $connection->getSchemaBuilder();
        $this->assertInstanceOf(Builder::class, $schema);
        $this->assertSame($connection, $schema->getConnection());
    }

    protected function getMockConnection($methods = [], $pdo = null)
    {
        $pdo = $pdo ?: new DatabaseConnectionTestMockPDO;
        $defaults = ['getDefaultQueryGrammar', 'getDefaultPostProcessor', 'getDefaultSchemaGrammar'];
        $connection = $this->getMockBuilder(Connection::class)->onlyMethods(array_merge($defaults, $methods))->setConstructorArgs([$pdo])->getMock();
        $connection->enableQueryLog();

        return $connection;
    }
}

class DatabaseConnectionTestMockPDO extends PDO
{
    public function __construct()
    {
        //
    }
}

class DatabaseConnectionTestMockPDOException extends PDOException
{
    /**
     * Overrides Exception::__construct, which casts $code to integer, so that we can create
     * an exception with a string $code consistent with the real PDOException behavior.
     *
     * @param  string|null  $message
     * @param  string|null  $code
     * @return void
     */
    public function __construct($message = null, $code = null)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
