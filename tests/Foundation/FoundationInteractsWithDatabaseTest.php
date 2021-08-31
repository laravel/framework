<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class FoundationInteractsWithDatabaseTest extends TestCase
{
    use InteractsWithDatabase;

    protected $table = 'products';

    protected $connection;

    protected function setUp(): void
    {
        $this->connection = m::mock(Connection::class);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testSeeInDatabaseFindsResults($data)
    {
        $this->mockCountBuilder(1, $data);

        $this->assertDatabaseHas($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertDatabaseHasSupportModels($data)
    {
        $this->mockCountBuilder(1, $data);

        $this->assertDatabaseHas(ProductStub::class, $data);
        $this->assertDatabaseHas(new ProductStub, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testSeeInDatabaseDoesNotFindResults($data)
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $builder = $this->mockCountBuilder(0, $data);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertDatabaseHas($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testSeeInDatabaseFindsNotMatchingResults($data)
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage('Found similar results: '.json_encode([['title' => 'Forge']], JSON_PRETTY_PRINT));

        $builder = $this->mockCountBuilder(0, $data);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(collect([['title' => 'Forge']]));

        $this->assertDatabaseHas($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testSeeInDatabaseFindsManyNotMatchingResults($data)
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage('Found similar results: '.json_encode(['data', 'data', 'data'], JSON_PRETTY_PRINT).' and 2 others.');

        $builder = $this->mockCountBuilder(0, $data);
        $builder->shouldReceive('count')->andReturn(0, 5);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(
            collect(array_fill(0, 3, 'data'))
        );

        $this->assertDatabaseHas($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testDontSeeInDatabaseDoesNotFindResults($data)
    {
        $this->mockCountBuilder(0, $data);

        $this->assertDatabaseMissing($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertDatabaseMissingSupportModels($data)
    {
        $this->mockCountBuilder(0, $data);

        $this->assertDatabaseMissing(ProductStub::class, $data);
        $this->assertDatabaseMissing(new ProductStub, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testDontSeeInDatabaseFindsResults($data)
    {
        $this->expectException(ExpectationFailedException::class);

        $builder = $this->mockCountBuilder(1, $data);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(collect([$data]));

        $this->assertDatabaseMissing($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertTableEntriesCount($data)
    {
        $this->mockCountBuilder(1, $data);

        $this->assertDatabaseCount($this->table, 1);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertDatabaseCountSupportModels($data)
    {
        $this->mockCountBuilder(1, $data);

        $this->assertDatabaseCount(ProductStub::class, 1);
        $this->assertDatabaseCount(new ProductStub, 1);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertTableEntriesCountWrong($data)
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that table [products] matches expected entries count of 3. Entries found: 1.');
        $this->mockCountBuilder(1, $data);

        $this->assertDatabaseCount($this->table, 3);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertDeletedPassesWhenDoesNotFindResults($data)
    {
        $this->mockCountBuilder(0, $data);

        $this->assertDatabaseMissing($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertDeletedFailsWhenFindsResults($data)
    {
        $this->expectException(ExpectationFailedException::class);

        $builder = $this->mockCountBuilder(1, $data);

        $builder->shouldReceive('get')->andReturn(collect([$data]));

        $this->assertDatabaseMissing($this->table, $data);
    }

    public function testAssertDeletedPassesWhenDoesNotFindModelResults()
    {
        $data = ['id' => 1];

        $builder = $this->mockCountBuilder(0, $data);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertDeleted(new ProductStub($data));
    }

    public function testAssertDeletedFailsWhenFindsModelResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $data = ['id' => 1];

        $builder = $this->mockCountBuilder(1, $data);

        $builder->shouldReceive('get')->andReturn(collect([$data]));

        $this->assertDeleted(new ProductStub($data));
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertSoftDeletedInDatabaseFindsResults($data)
    {
        $this->mockCountBuilder(1, $data);

        $this->assertSoftDeleted($this->table, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertSoftDeletedSupportModelStrings($data)
    {
        $this->mockCountBuilder(1, $data);

        $this->assertSoftDeleted(ProductStub::class, $data);
    }

    /**
     * @dataProvider whereConditionProvider
     */
    public function testAssertSoftDeletedInDatabaseDoesNotFindResults($data)
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $builder = $this->mockCountBuilder(0, $data);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted($this->table, $data);
    }

    public function testAssertSoftDeletedInDatabaseDoesNotFindModelResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $data = ['id' => 1];

        $builder = $this->mockCountBuilder(0, $data);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted(new ProductStub($data));
    }

    public function testAssertSoftDeletedInDatabaseDoesNotFindModelWithCustomColumnResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $data = ['id' => 1];

        $builder = $this->mockCountBuilder(0, $data, 'trashed_at');

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted(new CustomProductStub($data));
    }

    public function testGetTableNameFromModel()
    {
        $this->assertEquals($this->table, $this->getTable(ProductStub::class));
        $this->assertEquals($this->table, $this->getTable(new ProductStub));
        $this->assertEquals($this->table, $this->getTable($this->table));
    }

    protected function mockCountBuilder($countResult, $data, $deletedAtColumn = 'deleted_at')
    {
        $builder = m::mock(Builder::class);

        $firstCondition = array_slice($data, 0, 1, true);

        $builder->shouldReceive('where')->with($firstCondition)->andReturnSelf();

        $builder->shouldReceive('limit')->andReturnSelf();

        $builder->shouldReceive('where')->with($data)->andReturnSelf();

        $builder->shouldReceive('whereNotNull')->with($deletedAtColumn)->andReturnSelf();

        $builder->shouldReceive('count')->andReturn($countResult)->byDefault();

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->andReturn($builder);

        return $builder;
    }

    public function whereConditionProvider(): array
    {
        $simpleCondition = [
            'title' => 'Spark',
            'name' => 'Laravel',
        ];

        $complexCondition = [
            ['title', '=', 'Spark'],
            ['count', '>', 5],
        ];

        return [
            'simple' => [$simpleCondition],
            'complex' => [$complexCondition],
        ];
    }

    protected function getConnection()
    {
        return $this->connection;
    }
}

class ProductStub extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $guarded = [];
}

class CustomProductStub extends ProductStub
{
    const DELETED_AT = 'trashed_at';
}
