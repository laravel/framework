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

    public function testAssertDatabaseHasFindsResults()
    {
        $this->mockQueryBuilder(1);

        $this->assertDatabaseHas($this->table, $this->data);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     * @expectedExceptionMessage The table is empty.
     */
    public function testAssertDatabaseHasDoesNotFindResults()
    {
        $this->mockQueryBuilder(0, []);

        $this->assertDatabaseHas($this->table, $this->data);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertDatabaseHasFindsNotMatchingResults()
    {
        $this->expectExceptionMessage('Found: '.json_encode([['title' => 'Forge']], JSON_PRETTY_PRINT));

        $this->mockQueryBuilder(0, [['title' => 'Forge']]);

        $this->assertDatabaseHas($this->table, $this->data);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertDatabaseHasFindsManyNotMatchingResults()
    {
        $this->expectExceptionMessage('Found: '.json_encode(['data', 'data', 'data'], JSON_PRETTY_PRINT).' and 2 others.');

        $this->mockQueryBuilder(0, array_fill(0, 5, 'data'));

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testAssertDatabaseHasOneFindsOneResult()
    {
        $this->mockQueryBuilder(1);

        $this->assertDatabaseHas($this->table, $this->data);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     * @expectedExceptionMessage Failed asserting that exactly one row in the table [products] matches the attributes
     */
    public function testAssertDatabaseHasOneFindsManyResults()
    {
        $this->mockQueryBuilder(2, []);

        $this->assertDatabaseHasOne($this->table, $this->data);
    }

    public function testAssertDatabaseHasManyFindsTheExactAmountOfResults()
    {
        $this->mockQueryBuilder(3);

        $this->assertDatabaseHasMany($this->table, 3, $this->data);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     * @expectedExceptionMessage Failed asserting that 3 rows in the table [products] match the attributes
     */
    public function testAssertDatabaseHasManyDoesNotFindTheExactAmountOfResults()
    {
        $this->mockQueryBuilder(2, []);

        $this->assertDatabaseHasMany($this->table, 3, $this->data);
    }

    public function testAssertDatabaseMissingDoesNotFindResults()
    {
        $this->mockQueryBuilder(0);

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertDatabaseMissingFindsResults()
    {
        $this->mockQueryBuilder(1, $this->data);

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    public function testSeeSoftDeletedInDatabaseFindsResults()
    {
        $this->mockQueryBuilder(1);

        $this->assertSoftDeleted($this->table, $this->data);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     * @expectedExceptionMessage The table is empty.
     */
    public function testSeeSoftDeletedInDatabaseDoesNotFindResults()
    {
        $this->mockQueryBuilder(0, []);

        $this->assertSoftDeleted($this->table, $this->data);
    }

    protected function mockQueryBuilder($countResult, $data = null)
    {
        $builder = m::mock(Builder::class);

        $builder->shouldReceive('where')->with($this->data)->andReturnSelf();

        $builder->shouldReceive('whereNotNull')->with('deleted_at')->andReturnSelf();

        $builder->shouldReceive('count')->andReturn($countResult);

        if ($data !== null) {
            $builder->shouldReceive('get')->andReturn(collect($data));
        }

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
