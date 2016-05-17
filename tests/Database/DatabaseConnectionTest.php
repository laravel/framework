<?php

use Mockery as m;

class DatabaseConnectionTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSettingDefaultCallsGetDefaultGrammar()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock('StdClass');
        $connection->expects($this->once())->method('getDefaultQueryGrammar')->will($this->returnValue($mock));
        $connection->useDefaultQueryGrammar();
        $this->assertEquals($mock, $connection->getQueryGrammar());
    }

    public function testSettingDefaultCallsGetDefaultPostProcessor()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock('StdClass');
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
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO', ['prepare']);
        $writePdo = $this->getMock('DatabaseConnectionTestMockPDO', ['prepare']);
        $writePdo->expects($this->never())->method('prepare');
        $statement = $this->getMock('PDOStatement', ['execute', 'fetchAll']);
        $statement->expects($this->once())->method('execute')->with($this->equalTo(['foo' => 'bar']));
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
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO', ['prepare']);
        $statement = $this->getMock('PDOStatement', ['execute']);
        $statement->expects($this->once())->method('execute')->with($this->equalTo(['bar']))->will($this->returnValue('foo'));
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
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO', ['prepare']);
        $statement = $this->getMock('PDOStatement', ['execute', 'rowCount']);
        $statement->expects($this->once())->method('execute')->with($this->equalTo(['foo' => 'bar']));
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

    public function testTransactionsDecrementedOnTransactionException()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO');
        $pdo->expects($this->once())->method('beginTransaction')->will($this->throwException(new ErrorException('MySQL server has gone away')));
        $connection = $this->getMockConnection([], $pdo);
        $this->setExpectedException('ErrorException', 'MySQL server has gone away');
        $connection->beginTransaction();
        $connection->disconnect();
        $this->assertNull($connection->getPdo());
    }

    public function testCantSwapPDOWithOpenTransaction()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO');
        $pdo->expects($this->once())->method('beginTransaction')->will($this->returnValue(true));
        $connection = $this->getMockConnection([], $pdo);
        $connection->beginTransaction();
        $this->setExpectedException('RuntimeException', "Can't swap PDO instance while within transaction.");
        $connection->disconnect();
    }

    public function testBeganTransactionFiresEventsIfSet()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO');
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('fire')->once()->with(m::type('Illuminate\Database\Events\TransactionBeginning'));
        $connection->beginTransaction();
    }

    public function testCommitedFiresEventsIfSet()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO');
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('fire')->once()->with(m::type('Illuminate\Database\Events\TransactionCommitted'));
        $connection->commit();
    }

    public function testRollBackedFiresEventsIfSet()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO');
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('fire')->once()->with(m::type('Illuminate\Database\Events\TransactionRolledBack'));
        $connection->rollBack();
    }

    public function testTransactionMethodRunsSuccessfully()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO', ['beginTransaction', 'commit']);
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('commit');
        $result = $mock->transaction(function ($db) { return $db; });
        $this->assertEquals($mock, $result);
    }

    public function testTransactionMethodRollsbackAndThrows()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO', ['beginTransaction', 'commit', 'rollBack']);
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('rollBack');
        $pdo->expects($this->never())->method('commit');
        try {
            $mock->transaction(function () { throw new Exception('foo'); });
        } catch (Exception $e) {
            $this->assertEquals('foo', $e->getMessage());
        }
    }

    /**
     * @expectedException RuntimeException
     */
    public function testTransactionMethodDisallowPDOChanging()
    {
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO', ['beginTransaction', 'commit', 'rollBack']);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('rollBack');
        $pdo->expects($this->never())->method('commit');

        $mock = $this->getMockConnection([], $pdo);

        $mock->setReconnector(function ($connection) {
            $connection->setPDO(null);
        });

        $mock->transaction(function ($connection) { $connection->reconnect(); });
    }

    public function testRunMethodRetriesOnFailure()
    {
        $method = (new ReflectionClass('Illuminate\Database\Connection'))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->getMock('DatabaseConnectionTestMockPDO');
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $mock->expects($this->once())->method('tryAgainIfCausedByLostConnection');

        $method->invokeArgs($mock, ['', [], function () {throw new \Illuminate\Database\QueryException('', [], new \Exception); }]);
    }

    /**
     * @expectedException \Illuminate\Database\QueryException
     */
    public function testRunMethodNeverRetriesIfWithinTransaction()
    {
        $method = (new ReflectionClass('Illuminate\Database\Connection'))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->getMock('DatabaseConnectionTestMockPDO', ['beginTransaction']);
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $mock->expects($this->never())->method('tryAgainIfCausedByLostConnection');
        $mock->beginTransaction();

        $method->invokeArgs($mock, ['', [], function () {throw new \Illuminate\Database\QueryException('', [], new \Exception); }]);
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
        $events->shouldReceive('fire')->once()->with(m::type('Illuminate\Database\Events\QueryExecuted'));
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

    public function testAlternateFetchModes()
    {
        $stmt = $this->getMock('PDOStatement');
        $stmt->expects($this->exactly(3))->method('fetchAll')->withConsecutive(
            [PDO::FETCH_ASSOC],
            [PDO::FETCH_COLUMN, 3, []],
            [PDO::FETCH_CLASS, 'stdClass', [1, 2, 3]]
        );
        $pdo = $this->getMock('DatabaseConnectionTestMockPDO');
        $pdo->expects($this->any())->method('prepare')->will($this->returnValue($stmt));
        $connection = $this->getMockConnection([], $pdo);
        $connection->setFetchMode(PDO::FETCH_ASSOC);
        $connection->select('SELECT * FROM foo');
        $connection->setFetchMode(PDO::FETCH_COLUMN, 3);
        $connection->select('SELECT * FROM foo');
        $connection->setFetchMode(PDO::FETCH_CLASS, 'stdClass', [1, 2, 3]);
        $connection->select('SELECT * FROM foo');
    }

    protected function getMockConnection($methods = [], $pdo = null)
    {
        $pdo = $pdo ?: new DatabaseConnectionTestMockPDO;
        $defaults = ['getDefaultQueryGrammar', 'getDefaultPostProcessor', 'getDefaultSchemaGrammar'];
        $connection = $this->getMock('Illuminate\Database\Connection', array_merge($defaults, $methods), [$pdo]);
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
