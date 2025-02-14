<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentTransactionsTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
        $this->createSchema();
    }

    public function createSchema()
    {
        $this->schema()->create('stub', function ($table) {
            $table->increments('id');
            $table->string('foo');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->schema()->drop('stub');

        Carbon::setTestNow(null);
    }

    protected function schema()
    {
        $connection = Model::getConnectionResolver()->connection();

        return $connection->getSchemaBuilder();
    }

    public function testLockWithRefreshingModel()
    {
        $record = EloquentTransactionModelStub::create([
            'foo' => 'Baz',
        ]);

        $record->foo = 'Changed';
        $this->assertSame(['foo'], array_keys($record->getDirty()));

        DB::connection()->transaction(fn() => $record->lockForUpdate());

        $this->assertSame('Baz', $record->foo);
        $this->assertSame([], array_keys($record->getDirty()));
    }

    public function testLockWithoutRefreshingModel()
    {
        $record = EloquentTransactionModelStub::create([
            'foo' => 'Baz',
        ]);

        $record->foo = 'Changed';
        $this->assertSame(['foo'], array_keys($record->getDirty()));

        DB::connection()->transaction(fn() => $record->lockForUpdate(false));

        $this->assertSame('Changed', $record->foo);
        $this->assertSame(['foo'], array_keys($record->getDirty()));
    }

    public function testDontLockWithNotExistsRecord()
    {
        $record = new EloquentTransactionModelStub([
            'foo' => 'Baz',
        ]);

        $record->foo = 'Changed';

        DB::connection()->transaction(fn() => $record->lockForUpdate(true));

        $this->assertSame('Changed', $record->foo);
        $this->assertFalse($record->exists);
    }

    public function testLockWithMissingRecord()
    {
        $record = EloquentTransactionModelStub::create([
            'foo' => 'Baz',
        ]);

        EloquentTransactionModelStub::whereKey($record)->delete();

        $this->expectException(ModelNotFoundException::class);
        DB::connection()->transaction(fn() => $record->lockForUpdate(true));
    }

    public function testCollectionLockWithRefresh()
    {
        $collection = new Collection([
            EloquentTransactionModelStub::create(['foo' => '1']),
            EloquentTransactionModelStub::create(['foo' => '2']),
            EloquentTransactionModelStub::create(['foo' => '3']),
        ]);

        $collection[0]->foo = 'Changed';
        $collection[2]->foo = 'Changed';
        $this->assertSame(['foo'], array_keys($collection[0]->getDirty()));
        $this->assertSame(['foo'], array_keys($collection[2]->getDirty()));

        DB::connection()->transaction(fn() => $collection->lockForUpdate(true));

        $this->assertSame(['1', '2', '3'], [
            $collection[0]->foo,
            $collection[1]->foo,
            $collection[2]->foo,
        ]);
        $this->assertSame([], array_keys($collection[0]->getDirty()));
        $this->assertSame([], array_keys($collection[2]->getDirty()));
    }

    public function testCollectionLockWithoutRefresh()
    {
        $collection = new Collection([
            EloquentTransactionModelStub::create(['foo' => '1']),
            EloquentTransactionModelStub::create(['foo' => '2']),
            EloquentTransactionModelStub::create(['foo' => '3']),
        ]);

        $collection[0]->foo = 'Changed';
        $collection[2]->foo = 'Changed';
        $this->assertSame(['foo'], array_keys($collection[0]->getDirty()));
        $this->assertSame(['foo'], array_keys($collection[2]->getDirty()));

        DB::connection()->transaction(fn() => $collection->lockForUpdate(false));

        $this->assertSame(['Changed', '2', 'Changed'], [
            $collection[0]->foo,
            $collection[1]->foo,
            $collection[2]->foo,
        ]);
        $this->assertSame(['foo'], array_keys($collection[0]->getDirty()));
        $this->assertSame(['foo'], array_keys($collection[2]->getDirty()));
    }

    public function testCollectionLockWithNotExistsRecords()
    {
        $collection = new Collection([
            new EloquentTransactionModelStub(['foo' => '1']),
            new EloquentTransactionModelStub(['foo' => '2']),
        ]);

        $collection[0]->foo = 'Changed';
        $collection[1]->foo = 'Changed';

        DB::connection()->transaction(fn() => $collection->lockForUpdate(true));

        $this->assertSame(['Changed', 'Changed'], [
            $collection[0]->foo,
            $collection[1]->foo,
        ]);
    }

    public function testCollectionLockWithMissingRecords()
    {
        $collection = new Collection([
            EloquentTransactionModelStub::create(['foo' => 'Baz']),
        ]);

        EloquentTransactionModelStub::whereKey($collection[0])->delete();

        $this->expectException(ModelNotFoundException::class);
        DB::connection()->transaction(fn() => $collection->lockForUpdate(true));
    }

    public function testCollectionLockWithMissingRecordsShouldNotRefreshOthers()
    {
        $collection = new Collection([
            EloquentTransactionModelStub::create(['foo' => 'Baz']),
            EloquentTransactionModelStub::create(['foo' => 'Baz']),
        ]);

        EloquentTransactionModelStub::whereKey($collection[1])->delete();

        try {
            DB::connection()->transaction(fn() => $collection->lockForUpdate(true));
        } catch (ModelNotFoundException) {
        }

        $this->assertSame('Baz', $collection[0]->foo);
    }
}

class EloquentTransactionModelStub extends Model
{
    public $connection;
    protected $table = 'stub';
    protected $fillable = ['foo'];
}
