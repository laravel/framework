<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

class FoundationInteractsWithDatabaseTest extends TestCase
{
    use InteractsWithDatabase;

    protected $table = 'products';

    protected $data = ['title' => 'Spark'];

    protected $connection;

    public function setUp()
    {
        $this->connection = m::mock(Connection::class);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testSeeInDatabaseFindsResults()
    {
        $this->mockCountBuilder(1);

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testSeeInDatabaseDoesNotFindResults()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $builder = $this->mockCountBuilder(0);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testSeeInDatabaseFindsNotMatchingResults()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);

        $this->expectExceptionMessage('Found: '.json_encode([['title' => 'Forge']], JSON_PRETTY_PRINT));

        $builder = $this->mockCountBuilder(0);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(collect([['title' => 'Forge']]));

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testSeeInDatabaseFindsManyNotMatchingResults()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);

        $this->expectExceptionMessage('Found: '.json_encode(['data', 'data', 'data'], JSON_PRETTY_PRINT).' and 2 others.');

        $builder = $this->mockCountBuilder(0);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(
            collect(array_fill(0, 5, 'data'))
        );

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testDontSeeInDatabaseDoesNotFindResults()
    {
        $this->mockCountBuilder(0);

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    public function testDontSeeInDatabaseFindsResults()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);

        $builder = $this->mockCountBuilder(1);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(collect([$this->data]));

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    public function testSeeSoftDeletedInDatabaseFindsResults()
    {
        $this->mockCountBuilder(1);

        $this->assertSoftDeleted($this->table, $this->data);
    }

    public function testSeeSoftDeletedInDatabaseDoesNotFindResults()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $builder = $this->mockCountBuilder(0);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted($this->table, $this->data);
    }

    protected function mockCountBuilder($countResult)
    {
        $builder = m::mock(Builder::class);

        $builder->shouldReceive('where')->with($this->data)->andReturnSelf();

        $builder->shouldReceive('whereNotNull')->with('deleted_at')->andReturnSelf();

        $builder->shouldReceive('count')->andReturn($countResult);

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->andReturn($builder);

        return $builder;
    }

    protected function getConnection()
    {
        return $this->connection;
    }
}
