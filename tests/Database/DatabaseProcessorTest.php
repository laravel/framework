<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseProcessorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testInsertGetIdProcessing()
    {
        $pdo = $this->createMock(ProcessorTestPDOStub::class);
        $pdo->expects($this->once())->method('lastInsertId')->with($this->equalTo('id'))->willReturn('1');
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('insert')->once()->with('sql', ['foo']);
        $connection->shouldReceive('getPdo')->once()->andReturn($pdo);
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getConnection')->andReturn($connection);
        $processor = new Processor;
        $result = $processor->processInsertGetId($builder, 'sql', ['foo'], 'id');
        $this->assertSame(1, $result);
    }

    public function testInsertAndSetIdHandlesFalseGracefully()
    {
        // Mock the Builder to return false for insertGetId
        $builderMock = $this->createMock(\Illuminate\Database\Eloquent\Builder::class);
        $builderMock->method('insertGetId')->willReturn(false);

        // Mock the Model
        $modelMock = $this->getMockBuilder(\Illuminate\Database\Eloquent\Model::class)
            ->onlyMethods(['getKeyName', 'setAttribute'])
            ->getMock();

        $modelMock->method('getKeyName')->willReturn('id');

        // Expect setAttribute NOT to be called since ID should not be set
        $modelMock->expects($this->never())->method('setAttribute');

        // Call the protected method using reflection
        $reflection = new \ReflectionMethod($modelMock, 'insertAndSetId');
        $reflection->setAccessible(true);
        $reflection->invoke($modelMock, $builderMock, ['name' => 'Test']);
    }
}

class ProcessorTestPDOStub extends PDO
{
    public function __construct()
    {
        //
    }

    public function lastInsertId($sequence = null): string|false
    {
        return '';
    }
}
