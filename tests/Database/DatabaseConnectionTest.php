<?php

namespace Illuminate\Tests\Database;

use PDO;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSettingDefaultCallsGetDefaultGrammar()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock('stdClass');
        $connection->expects($this->once())->method('getDefaultQueryGrammar')->will($this->returnValue($mock));
        $connection->useDefaultQueryGrammar();
        $this->assertEquals($mock, $connection->getQueryGrammar());
    }

    public function testSettingDefaultCallsGetDefaultPostProcessor()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock('stdClass');
        $connection->expects($this->once())->method('getDefaultPostProcessor')->will($this->returnValue($mock));
        $connection->useDefaultPostProcessor();
        $this->assertEquals($mock, $connection->getPostProcessor());
    }

    public function testSelectOneCallsSelectAndReturnsSingleResult()
    {
        $connection = $this->getMockConnection(['select']);
        $connection->expects($this->once())->method('select')->with('foo', ['bar' => 'baz'])->will($this->returnValue(['foo']));
        $this->assertEquals('foo', $connection->selectOne('foo', ['bar' => 'baz']));
    }

    public function testSelectProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['prepare'])->getMock();
        $writePdo->expects($this->never())->method('prepare');
        $statement = $this->getMockBuilder('PDOStatement')->setMethods(['execute', 'fetchAll', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));
        $pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $mock = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mock->setReadPdo($pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['foo' => 'bar']))->will($this->returnValue(['foo' => 'bar']));
        $results = $mock->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
        $log = $mock->getQueryLog();
        $this->assertEquals('foo', $log[0]['query']);
        $this->assertEquals(['foo' => 'bar'], $log[0]['bindings']);
        $this->assertTrue(is_numeric($log[0]['time']));
    }

    public function testInsertCallsTheStatementMethod()
    {
        $connection = $this->getMockConnection(['statement']);
        $connection->expects($this->once())->method('statement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->will($this->returnValue('baz'));
        $results = $connection->insert('foo', ['bar']);
        $this->assertEquals('baz', $results);
    }

    public function testUpdateCallsTheAffectingStatementMethod()
    {
        $connection = $this->getMockConnection(['affectingStatement']);
        $connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->will($this->returnValue('baz'));
        $results = $connection->update('foo', ['bar']);
        $this->assertEquals('baz', $results);
    }

    public function testDeleteCallsTheAffectingStatementMethod()
    {
        $connection = $this->getMockConnection(['affectingStatement']);
        $connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->will($this->returnValue('baz'));
        $results = $connection->delete('foo', ['bar']);
        $this->assertEquals('baz', $results);
    }

    public function testStatementProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder('PDOStatement')->setMethods(['execute', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with(1, 'bar', 2);
        $statement->expects($this->once())->method('execute')->will($this->returnValue('foo'));
        $pdo->expects($this->once())->method('prepare')->with($this->equalTo('foo'))->will($this->returnValue($statement));
        $mock = $this->getMockConnection(['prepareBindings'], $pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['bar']))->will($this->returnValue(['bar']));
        $results = $mock->statement('foo', ['bar']);
        $this->assertEquals('foo', $results);
        $log = $mock->getQueryLog();
        $this->assertEquals('foo', $log[0]['query']);
        $this->assertEquals(['bar'], $log[0]['bindings']);
        $this->assertTrue(is_numeric($log[0]['time']));
    }

    public function testAffectingStatementProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder('PDOStatement')->setMethods(['execute', 'rowCount', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('rowCount')->will($this->returnValue(['boom']));
        $pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $mock = $this->getMockConnection(['prepareBindings'], $pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['foo' => 'bar']))->will($this->returnValue(['foo' => 'bar']));
        $results = $mock->update('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
        $log = $mock->getQueryLog();
        $this->assertEquals('foo', $log[0]['query']);
        $this->assertEquals(['foo' => 'bar'], $log[0]['bindings']);
        $this->assertTrue(is_numeric($log[0]['time']));
    }

    public function testTransactionLevelNotIncrementedOnTransactionException()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $pdo->expects($this->once())->method('beginTransaction')->will($this->throwException(new \Exception));
        $connection = $this->getMockConnection([], $pdo);
        try {
            $connection->beginTransaction();
        } catch (\Exception $e) {
            $this->assertEquals(0, $connection->transactionLevel());
        }
    }

    public function testBeginTransactionMethodRetriesOnFailure()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $pdo->expects($this->exactly(2))->method('beginTransaction');
        $pdo->expects($this->at(0))->method('beginTransaction')->will($this->throwException(new \ErrorException('server has gone away')));
        $connection = $this->getMockConnection(['reconnect'], $pdo);
        $connection->expects($this->once())->method('reconnect');
        $connection->beginTransaction();
        $this->assertEquals(1, $connection->transactionLevel());
    }

    public function testBeginTransactionMethodNeverRetriesIfWithinTransaction()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('exec')->will($this->throwException(new \Exception));
        $connection = $this->getMockConnection(['reconnect'], $pdo);
        $queryGrammar = $this->createMock('Illuminate\Database\Query\Grammars\Grammar');
        $queryGrammar->expects($this->once())->method('supportsSavepoints')->will($this->returnValue(true));
        $connection->setQueryGrammar($queryGrammar);
        $connection->expects($this->never())->method('reconnect');
        $connection->beginTransaction();
        $this->assertEquals(1, $connection->transactionLevel());
        try {
            $connection->beginTransaction();
        } catch (\Exception $e) {
            $this->assertEquals(1, $connection->transactionLevel());
        }
    }

    public function testSwapPDOWithOpenTransactionResetsTransactionLevel()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $pdo->expects($this->once())->method('beginTransaction')->will($this->returnValue(true));
        $connection = $this->getMockConnection([], $pdo);
        $connection->beginTransaction();
        $connection->disconnect();
        $this->assertEquals(0, $connection->transactionLevel());
    }

    public function testBeganTransactionFiresEventsIfSet()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('dispatch')->once()->with(m::type('Illuminate\Database\Events\TransactionBeginning'));
        $connection->beginTransaction();
    }

    public function testCommittedFiresEventsIfSet()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('dispatch')->once()->with(m::type('Illuminate\Database\Events\TransactionCommitted'));
        $connection->commit();
    }

    public function testRollBackedFiresEventsIfSet()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->beginTransaction();
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('dispatch')->once()->with(m::type('Illuminate\Database\Events\TransactionRolledBack'));
        $connection->rollBack();
    }

    public function testRedundantRollBackFiresNoEvent()
    {
        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldNotReceive('dispatch');
        $connection->rollBack();
    }

    public function testTransactionMethodRunsSuccessfully()
    {
        $pdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['beginTransaction', 'commit'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('commit');
        $result = $mock->transaction(function ($db) {
            return $db;
        });
        $this->assertEquals($mock, $result);
    }

    /**
     * @expectedException \Illuminate\Database\QueryException
     * @expectedExceptionMessage Deadlock found when trying to get lock (SQL: )
     */
    public function testTransactionMethodRetriesOnDeadlock()
    {
        $pdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['beginTransaction', 'commit', 'rollBack'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->exactly(3))->method('beginTransaction');
        $pdo->expects($this->exactly(3))->method('rollBack');
        $pdo->expects($this->never())->method('commit');
        $mock->transaction(function () {
            throw new \Illuminate\Database\QueryException('', [], new \Exception('Deadlock found when trying to get lock'));
        }, 3);
    }

    public function testTransactionMethodRollsbackAndThrows()
    {
        $pdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['beginTransaction', 'commit', 'rollBack'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('rollBack');
        $pdo->expects($this->never())->method('commit');
        try {
            $mock->transaction(function () {
                throw new \Exception('foo');
            });
        } catch (\Exception $e) {
            $this->assertEquals('foo', $e->getMessage());
        }
    }

    /**
     * @expectedException \Illuminate\Database\QueryException
     * @expectedExceptionMessage server has gone away (SQL: foo)
     */
    public function testOnLostConnectionPDOIsNotSwappedWithinATransaction()
    {
        $pdo = m::mock(\PDO::class);
        $pdo->shouldReceive('beginTransaction')->once();
        $statement = m::mock(\PDOStatement::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);
        $statement->shouldReceive('execute')->once()->andThrow(new \PDOException('server has gone away'));

        $connection = new \Illuminate\Database\Connection($pdo);
        $connection->beginTransaction();
        $connection->statement('foo');
    }

    public function testOnLostConnectionPDOIsSwappedOutsideTransaction()
    {
        $pdo = m::mock(\PDO::class);

        $statement = m::mock(\PDOStatement::class);
        $statement->shouldReceive('execute')->once()->andThrow(new \PDOException('server has gone away'));
        $statement->shouldReceive('execute')->once()->andReturn('result');

        $pdo->shouldReceive('prepare')->twice()->andReturn($statement);

        $connection = new \Illuminate\Database\Connection($pdo);

        $called = false;

        $connection->setReconnector(function ($connection) use (&$called) {
            $called = true;
        });

        $this->assertEquals('result', $connection->statement('foo'));

        $this->assertTrue($called);
    }

    public function testRunMethodRetriesOnFailure()
    {
        $method = (new \ReflectionClass('Illuminate\Database\Connection'))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->createMock('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO');
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $mock->expects($this->once())->method('tryAgainIfCausedByLostConnection');

        $method->invokeArgs($mock, ['', [], function () {
            throw new \Illuminate\Database\QueryException('', [], new \Exception);
        }]);
    }

    /**
     * @expectedException \Illuminate\Database\QueryException
     * @expectedExceptionMessage  (SQL: ) (SQL: )
     */
    public function testRunMethodNeverRetriesIfWithinTransaction()
    {
        $method = (new \ReflectionClass('Illuminate\Database\Connection'))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->getMockBuilder('Illuminate\Tests\Database\DatabaseConnectionTestMockPDO')->setMethods(['beginTransaction'])->getMock();
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $mock->expects($this->never())->method('tryAgainIfCausedByLostConnection');
        $mock->beginTransaction();

        $method->invokeArgs($mock, ['', [], function () {
            throw new \Illuminate\Database\QueryException('', [], new \Exception);
        }]);
    }

    public function testFromCreatesNewQueryBuilder()
    {
        $conn = $this->getMockConnection();
        $conn->setQueryGrammar(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
        $conn->setPostProcessor(m::mock('Illuminate\Database\Query\Processors\Processor'));
        $builder = $conn->table('users');
        $this->assertInstanceOf('Illuminate\Database\Query\Builder', $builder);
        $this->assertEquals('users', $builder->from);
    }

    public function testPrepareBindings()
    {
        $date = m::mock('DateTime');
        $date->shouldReceive('format')->once()->with('foo')->andReturn('bar');
        $bindings = ['test' => $date];
        $conn = $this->getMockConnection();
        $grammar = m::mock('Illuminate\Database\Query\Grammars\Grammar');
        $grammar->shouldReceive('getDateFormat')->once()->andReturn('foo');
        $conn->setQueryGrammar($grammar);
        $result = $conn->prepareBindings($bindings);
        $this->assertEquals(['test' => 'bar'], $result);
    }

    public function testLogQueryFiresEventsIfSet()
    {
        $connection = $this->getMockConnection();
        $connection->logQuery('foo', [], time());
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('dispatch')->once()->with(m::type('Illuminate\Database\Events\QueryExecuted'));
        $connection->logQuery('foo', [], null);
    }

    public function testPretendOnlyLogsQueries()
    {
        $connection = $this->getMockConnection();
        $queries = $connection->pretend(function ($connection) {
            $connection->select('foo bar', ['baz']);
        });
        $this->assertEquals('foo bar', $queries[0]['query']);
        $this->assertEquals(['baz'], $queries[0]['bindings']);
    }

    public function testSchemaBuilderCanBeCreated()
    {
        $connection = $this->getMockConnection();
        $schema = $connection->getSchemaBuilder();
        $this->assertInstanceOf('Illuminate\Database\Schema\Builder', $schema);
        $this->assertSame($connection, $schema->getConnection());
    }

    protected function getMockConnection($methods = [], $pdo = null)
    {
        $pdo = $pdo ?: new DatabaseConnectionTestMockPDO;
        $defaults = ['getDefaultQueryGrammar', 'getDefaultPostProcessor', 'getDefaultSchemaGrammar'];
        $connection = $this->getMockBuilder('Illuminate\Database\Connection')->setMethods(array_merge($defaults, $methods))->setConstructorArgs([$pdo])->getMock();
        $connection->enableQueryLog();

        return $connection;
    }
}

class DatabaseConnectionTestMockPDO extends PDO
{
    public function __construct()
    {
    }
}
