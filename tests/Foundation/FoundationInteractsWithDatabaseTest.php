<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\TestCase as TestingTestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class FoundationInteractsWithDatabaseTest extends TestCase
{
    use InteractsWithDatabase;

    protected $table = 'products';

    protected $data = [
        'title' => 'Spark',
        'name' => 'Laravel',
    ];

    protected $connection;

    protected function setUp(): void
    {
        $this->connection = m::mock(Connection::class);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testSeeInDatabaseFindsResults()
    {
        $this->mockCountBuilder(true);

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testAssertDatabaseHasSupportsModelClass()
    {
        $this->mockCountBuilder(true);

        $this->assertDatabaseHas(ProductStub::class, $this->data);
    }

    public function testAssertDatabaseHasConstrainsToModel()
    {
        $data = $this->data;

        $this->data = [
            'id' => 1,
            ...$this->data,
        ];

        $this->mockCountBuilder(true);

        $this->assertDatabaseHas(new ProductStub(['id' => 1]), $data);
    }

    public function testSeeInDatabaseDoesNotFindResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testSeeInDatabaseFindsNotMatchingResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage('Found similar results: '.json_encode([['title' => 'Forge']], JSON_PRETTY_PRINT));

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(collect([['title' => 'Forge']]));

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testSeeInDatabaseFindsManyNotMatchingResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage('Found similar results: '.json_encode(['data', 'data', 'data'], JSON_PRETTY_PRINT).' and 2 others.');

        $builder = $this->mockCountBuilder(false, countResult: [5, 5]);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(
            collect(array_fill(0, 3, 'data'))
        );

        $this->assertDatabaseHas($this->table, $this->data);
    }

    public function testDontSeeInDatabaseDoesNotFindResults()
    {
        $this->mockCountBuilder(false);

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    public function testAssertDatabaseMissingSupportsModelClass()
    {
        $this->mockCountBuilder(false);

        $this->assertDatabaseMissing(ProductStub::class, $this->data);
    }

    public function testAssertDatabaseMissingConstrainsToModel()
    {
        $data = $this->data;

        $this->data = [
            'id' => 1,
            ...$this->data,
        ];

        $this->mockCountBuilder(false);

        $this->assertDatabaseMissing(new ProductStub(['id' => 1]), $data);
    }

    public function testDontSeeInDatabaseFindsResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $builder = $this->mockCountBuilder(true);

        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(collect([$this->data]));

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    public function testAssertTableEntriesCount()
    {
        $this->mockCountBuilder(true);

        $this->assertDatabaseCount($this->table, 1);
    }

    public function testAssertDatabaseCountSupportModels()
    {
        $this->mockCountBuilder(true);

        $this->assertDatabaseCount(ProductStub::class, 1);
        $this->assertDatabaseCount(new ProductStub, 1);
    }

    public function testAssertDatabaseEmpty()
    {
        $this->mockCountBuilder(false);

        $this->assertDatabaseEmpty(ProductStub::class);
        $this->assertDatabaseEmpty(new ProductStub);
    }

    public function testAssertTableEntriesCountWrong()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that table [products] matches expected entries count of 3. Entries found: 1.');
        $this->mockCountBuilder(true);

        $this->assertDatabaseCount($this->table, 3);
    }

    public function testAssertDatabaseMissingPassesWhenDoesNotFindResults()
    {
        $this->mockCountBuilder(false);

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    public function testAssertDatabaseMissingFailsWhenFindsResults()
    {
        $this->expectException(ExpectationFailedException::class);

        $builder = $this->mockCountBuilder(true);

        $builder->shouldReceive('get')->andReturn(collect([$this->data]));

        $this->assertDatabaseMissing($this->table, $this->data);
    }

    public function testAssertModelMissingPassesWhenDoesNotFindModelResults()
    {
        $this->data = ['id' => 1];

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertModelMissing(new ProductStub($this->data));
    }

    public function testAssertSoftDeletedInDatabaseFindsResults()
    {
        $this->mockCountBuilder(true);

        $this->assertSoftDeleted($this->table, $this->data);
    }

    public function testAssertSoftDeletedSupportModelStrings()
    {
        $this->mockCountBuilder(true);

        $this->assertSoftDeleted(ProductStub::class, $this->data);
    }

    public function testAssertSoftDeletedInDatabaseDoesNotFindResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted($this->table, $this->data);
    }

    public function testAssertSoftDeletedInDatabaseDoesNotFindModelResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $this->data = ['id' => 1];

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted(new ProductStub($this->data));
    }

    public function testAssertSoftDeletedInDatabaseDoesNotFindModelWithCustomColumnResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $model = new CustomProductStub(['id' => 1, 'name' => 'Laravel']);
        $this->data = ['id' => 1, 'name' => 'Tailwind'];

        $builder = $this->mockCountBuilder(false, 'trashed_at');

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted($model, ['name' => 'Tailwind']);
    }

    public function testAssertSoftDeletedInDatabaseDoesNotFindModePassedViaFcnWithCustomColumnResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $model = new CustomProductStub(['id' => 1, 'name' => 'Laravel']);
        $this->data = ['id' => 1];

        $builder = $this->mockCountBuilder(false, 'trashed_at');

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertSoftDeleted(CustomProductStub::class, ['id' => $model->id]);
    }

    public function testAssertNotSoftDeletedInDatabaseFindsResults()
    {
        $this->mockCountBuilder(true);

        $this->assertNotSoftDeleted($this->table, $this->data);
    }

    public function testAssertNotSoftDeletedSupportModelStrings()
    {
        $this->mockCountBuilder(true);

        $this->assertNotSoftDeleted(ProductStub::class, $this->data);
    }

    public function testAssertNotSoftDeletedOnlyFindsMatchingModels()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that any existing row');

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('get')->andReturn(collect(), collect(1));

        $this->assertNotSoftDeleted(ProductStub::class, $this->data);
    }

    public function testAssertNotSoftDeletedInDatabaseDoesNotFindResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertNotSoftDeleted($this->table, $this->data);
    }

    public function testAssertNotSoftDeletedInDatabaseDoesNotFindModelResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $this->data = ['id' => 1];

        $builder = $this->mockCountBuilder(false);

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertNotSoftDeleted(new ProductStub($this->data));
    }

    public function testAssertNotSoftDeletedInDatabaseDoesNotFindModelWithCustomColumnResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $model = new CustomProductStub(['id' => 1, 'name' => 'Laravel']);
        $this->data = ['id' => 1, 'name' => 'Tailwind'];

        $builder = $this->mockCountBuilder(false, 'trashed_at');

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertNotSoftDeleted($model, ['name' => 'Tailwind']);
    }

    public function testAssertNotSoftDeletedInDatabaseDoesNotFindModelPassedViaFcnWithCustomColumnResults()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The table is empty.');

        $model = new CustomProductStub(['id' => 1, 'name' => 'Laravel']);
        $this->data = ['id' => 1];

        $builder = $this->mockCountBuilder(false, 'trashed_at');

        $builder->shouldReceive('get')->andReturn(collect());

        $this->assertNotSoftDeleted(CustomProductStub::class, ['id' => $model->id]);
    }

    public function testAssertExistsPassesWhenFindsResults()
    {
        $this->data = ['id' => 1];

        $builder = $this->mockCountBuilder(true);

        $builder->shouldReceive('get')->andReturn(collect($this->data));

        $this->assertModelExists(new ProductStub($this->data));
    }

    public function testGetTableNameFromModel()
    {
        $this->assertEquals($this->table, $this->getTable(ProductStub::class));
        $this->assertEquals($this->table, $this->getTable(new ProductStub));
        $this->assertEquals($this->table, $this->getTable($this->table));
        $this->assertEquals('all_products', $this->getTable((new ProductStub)->setTable('all_products')));
    }

    public function testGetTableConnectionNameFromModel()
    {
        $this->assertSame(null, $this->getTableConnection(ProductStub::class));
        $this->assertSame(null, $this->getTableConnection(new ProductStub));
        $this->assertSame('mysql', $this->getTableConnection((new ProductStub)->setConnection('mysql')));
    }

    public function testGetTableCustomizedDeletedAtColumnName()
    {
        $this->assertEquals('trashed_at', $this->getDeletedAtColumn(CustomProductStub::class));
        $this->assertEquals('trashed_at', $this->getDeletedAtColumn(new CustomProductStub()));
    }

    public function testExpectsDatabaseQueryCount()
    {
        $case = new class('foo') extends TestingTestCase
        {
            use CreatesApplication;

            public function testExpectsDatabaseQueryCount()
            {
                $this->expectsDatabaseQueryCount(0);
            }
        };

        $case->setUp();
        $case->testExpectsDatabaseQueryCount();
        $case->tearDown();

        $case = new class('foo') extends TestingTestCase
        {
            use CreatesApplication;

            public function testExpectsDatabaseQueryCount()
            {
                $this->expectsDatabaseQueryCount(3);
            }
        };

        $case->setUp();
        $case->testExpectsDatabaseQueryCount();

        try {
            $case->tearDown();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertSame("Expected 3 database queries on the [testing] connection. 0 occurred.\nFailed asserting that 0 is identical to 3.", $e->getMessage());
        }

        $case = new class('foo') extends TestingTestCase
        {
            use CreatesApplication;

            public function testExpectsDatabaseQueryCount()
            {
                $this->expectsDatabaseQueryCount(3);

                DB::pretend(function ($db) {
                    $db->table('foo')->count();
                    $db->table('foo')->count();
                    $db->table('foo')->count();
                    $db->table('foo')->count();
                });
            }
        };

        $case->setUp();
        $case->testExpectsDatabaseQueryCount();

        try {
            $case->tearDown();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertSame("Expected 3 database queries on the [testing] connection. 4 occurred.\nFailed asserting that 4 is identical to 3.", $e->getMessage());
        }

        $case = new class('foo') extends TestingTestCase
        {
            use CreatesApplication;

            public function testExpectsDatabaseQueryCount()
            {
                $this->expectsDatabaseQueryCount(4);
                $this->expectsDatabaseQueryCount(1, 'mysql');

                DB::pretend(function ($db) {
                    $db->table('foo')->count();
                    $db->table('foo')->count();
                    $db->table('foo')->count();
                });

                DB::connection('mysql')->pretend(function ($db) {
                    $db->table('foo')->count();
                });
            }
        };

        $case->setUp();
        $case->testExpectsDatabaseQueryCount();
        $case->tearDown();
    }

    protected function mockCountBuilder($existsResult, $deletedAtColumn = 'deleted_at', $countResult = null)
    {
        $builder = m::mock(Builder::class);

        $countResult = Arr::wrap($countResult);
        $countResult = ! empty($countResult) ? $countResult : [$existsResult ? 1 : 0];

        $key = array_key_first($this->data);
        $value = $this->data[$key];

        $builder->shouldReceive('where')->with($key, $value)->andReturnSelf();

        $builder->shouldReceive('select')->with(array_keys($this->data))->andReturnSelf();

        $builder->shouldReceive('limit')->andReturnSelf();

        $builder->shouldReceive('where')->with($this->data)->andReturnSelf();

        $builder->shouldReceive('whereNotNull')->with($deletedAtColumn)->andReturnSelf();

        $builder->shouldReceive('whereNull')->with($deletedAtColumn)->andReturnSelf();

        $builder->shouldReceive('exists')->andReturn($existsResult)->byDefault();

        $builder->shouldReceive('count')->andReturn(...$countResult)->byDefault();

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
