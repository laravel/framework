<?php

namespace Illuminate\Tests\Database;

use DateTime;
use stdClass;
use Exception;
use Mockery as m;
use Carbon\Carbon;
use LogicException;
use ReflectionClass;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class DatabaseEloquentModelTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        Carbon::setTestNow(Carbon::now());
    }

    public function tearDown()
    {
        parent::tearDown();

        m::close();
        Carbon::setTestNow(null);

        \Illuminate\Database\Eloquent\Model::unsetEventDispatcher();
        \Carbon\Carbon::resetToStringFormat();
    }

    public function testAttributeManipulation()
    {
        $model = new EloquentModelStub;
        $model->name = 'foo';
        $this->assertEquals('foo', $model->name);
        $this->assertTrue(isset($model->name));
        unset($model->name);
        $this->assertFalse(isset($model->name));

        // test mutation
        $model->list_items = ['name' => 'taylor'];
        $this->assertEquals(['name' => 'taylor'], $model->list_items);
        $attributes = $model->getAttributes();
        $this->assertEquals(json_encode(['name' => 'taylor']), $attributes['list_items']);
    }

    public function testDirtyAttributes()
    {
        $model = new EloquentModelStub(['foo' => '1', 'bar' => 2, 'baz' => 3]);
        $model->syncOriginal();
        $model->foo = 1;
        $model->bar = 20;
        $model->baz = 30;

        $this->assertTrue($model->isDirty());
        $this->assertFalse($model->isDirty('foo'));
        $this->assertTrue($model->isDirty('bar'));
        $this->assertTrue($model->isDirty('foo', 'bar'));
        $this->assertTrue($model->isDirty(['foo', 'bar']));
    }

    public function testCleanAttributes()
    {
        $model = new EloquentModelStub(['foo' => '1', 'bar' => 2, 'baz' => 3]);
        $model->syncOriginal();
        $model->foo = 1;
        $model->bar = 20;
        $model->baz = 30;

        $this->assertFalse($model->isClean());
        $this->assertTrue($model->isClean('foo'));
        $this->assertFalse($model->isClean('bar'));
        $this->assertFalse($model->isClean('foo', 'bar'));
        $this->assertFalse($model->isClean(['foo', 'bar']));
    }

    public function testCalculatedAttributes()
    {
        $model = new EloquentModelStub;
        $model->password = 'secret';
        $attributes = $model->getAttributes();

        // ensure password attribute was not set to null
        $this->assertArrayNotHasKey('password', $attributes);
        $this->assertEquals('******', $model->password);

        $hash = 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4';

        $this->assertEquals($hash, $attributes['password_hash']);
        $this->assertEquals($hash, $model->password_hash);
    }

    public function testArrayAccessToAttributes()
    {
        $model = new EloquentModelStub(['attributes' => 1, 'connection' => 2, 'table' => 3]);
        unset($model['table']);

        $this->assertTrue(isset($model['attributes']));
        $this->assertEquals($model['attributes'], 1);
        $this->assertTrue(isset($model['connection']));
        $this->assertEquals($model['connection'], 2);
        $this->assertFalse(isset($model['table']));
        $this->assertEquals($model['table'], null);
        $this->assertFalse(isset($model['with']));
    }

    public function testNewInstanceReturnsNewInstanceWithAttributesSet()
    {
        $model = new EloquentModelStub;
        $instance = $model->newInstance(['name' => 'taylor']);
        $this->assertInstanceOf('Illuminate\Tests\Database\EloquentModelStub', $instance);
        $this->assertEquals('taylor', $instance->name);
    }

    public function testCreateMethodSavesNewModel()
    {
        $_SERVER['__eloquent.saved'] = false;
        $model = EloquentModelSaveStub::create(['name' => 'taylor']);
        $this->assertTrue($_SERVER['__eloquent.saved']);
        $this->assertEquals('taylor', $model->name);
    }

    public function testForceCreateMethodSavesNewModelWithGuardedAttributes()
    {
        $_SERVER['__eloquent.saved'] = false;
        $model = EloquentModelSaveStub::forceCreate(['id' => 21]);
        $this->assertTrue($_SERVER['__eloquent.saved']);
        $this->assertEquals(21, $model->id);
    }

    public function testFindMethodUseWritePdo()
    {
        $result = EloquentModelFindWithWritePdoStub::onWriteConnection()->find(1);
    }

    public function testDestroyMethodCallsQueryBuilderCorrectly()
    {
        $result = EloquentModelDestroyStub::destroy(1, 2, 3);
    }

    public function testWithMethodCallsQueryBuilderCorrectly()
    {
        $result = EloquentModelWithStub::with('foo', 'bar');
        $this->assertEquals('foo', $result);
    }

    public function testWithoutMethodRemovesEagerLoadedRelationshipCorrectly()
    {
        $model = new EloquentModelWithoutRelationStub;
        $instance = $model->newInstance()->newQuery()->without('foo');
        $this->assertEmpty($instance->getEagerLoads());
    }

    public function testEagerLoadingWithColumns()
    {
        $model = new EloquentModelWithoutRelationStub;
        $instance = $model->newInstance()->newQuery()->with('foo:bar,baz', 'hadi');
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('select')->once()->with(['bar', 'baz']);
        $this->assertNotNull($instance->getEagerLoads()['hadi']);
        $this->assertNotNull($instance->getEagerLoads()['foo']);
        $closure = $instance->getEagerLoads()['foo'];
        $closure($builder);
    }

    public function testWithMethodCallsQueryBuilderCorrectlyWithArray()
    {
        $result = EloquentModelWithStub::with(['foo', 'bar']);
        $this->assertEquals('foo', $result);
    }

    public function testUpdateProcess()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['name' => 'taylor'])->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');
        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('fire')->once()->with('eloquent.updated: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('fire')->once()->with('eloquent.saved: '.get_class($model), $model)->andReturn(true);

        $model->id = 1;
        $model->foo = 'bar';
        // make sure foo isn't synced so we can test that dirty attributes only are updated
        $model->syncOriginal();
        $model->name = 'taylor';
        $model->exists = true;
        $this->assertTrue($model->save());
    }

    public function testUpdateProcessDoesntOverrideTimestamps()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['created_at' => 'foo', 'updated_at' => 'bar'])->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until');
        $events->shouldReceive('fire');

        $model->id = 1;
        $model->syncOriginal();
        $model->created_at = 'foo';
        $model->updated_at = 'bar';
        $model->exists = true;
        $this->assertTrue($model->save());
    }

    public function testSaveIsCancelledIfSavingEventReturnsFalse()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(false);
        $model->exists = true;

        $this->assertFalse($model->save());
    }

    public function testUpdateIsCancelledIfUpdatingEventReturnsFalse()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(false);
        $model->exists = true;
        $model->foo = 'bar';

        $this->assertFalse($model->save());
    }

    public function testEventsCanBeFiredWithCustomEventObjects()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelEventObjectStub')->setMethods(['newQueryWithoutScopes'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with(m::type(EloquentModelSavingEventStub::class))->andReturn(false);
        $model->exists = true;

        $this->assertFalse($model->save());
    }

    public function testUpdateProcessWithoutTimestamps()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelEventObjectStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'fireModelEvent'])->getMock();
        $model->timestamps = false;
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['name' => 'taylor'])->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->never())->method('updateTimestamps');
        $model->expects($this->any())->method('fireModelEvent')->will($this->returnValue(true));

        $model->id = 1;
        $model->syncOriginal();
        $model->name = 'taylor';
        $model->exists = true;
        $this->assertTrue($model->save());
    }

    public function testUpdateUsesOldPrimaryKey()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['id' => 2, 'foo' => 'bar'])->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');
        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('fire')->once()->with('eloquent.updated: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('fire')->once()->with('eloquent.saved: '.get_class($model), $model)->andReturn(true);

        $model->id = 1;
        $model->syncOriginal();
        $model->id = 2;
        $model->foo = 'bar';
        $model->exists = true;

        $this->assertTrue($model->save());
    }

    public function testTimestampsAreReturnedAsObjects()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentDateModelStub')->setMethods(['getDateFormat'])->getMock();
        $model->expects($this->any())->method('getDateFormat')->will($this->returnValue('Y-m-d'));
        $model->setRawAttributes([
            'created_at' => '2012-12-04',
            'updated_at' => '2012-12-05',
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $model->created_at);
        $this->assertInstanceOf('Carbon\Carbon', $model->updated_at);
    }

    public function testTimestampsAreReturnedAsObjectsFromPlainDatesAndTimestamps()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentDateModelStub')->setMethods(['getDateFormat'])->getMock();
        $model->expects($this->any())->method('getDateFormat')->will($this->returnValue('Y-m-d H:i:s'));
        $model->setRawAttributes([
            'created_at' => '2012-12-04',
            'updated_at' => Carbon::now()->getTimestamp(),
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $model->created_at);
        $this->assertInstanceOf('Carbon\Carbon', $model->updated_at);
    }

    public function testTimestampsAreReturnedAsObjectsOnCreate()
    {
        $timestamps = [
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ];
        $model = new EloquentDateModelStub;
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
        $resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock('StdClass'));
        $mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
        $mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $instance = $model->newInstance($timestamps);
        $this->assertInstanceOf('Carbon\Carbon', $instance->updated_at);
        $this->assertInstanceOf('Carbon\Carbon', $instance->created_at);
    }

    public function testDateTimeAttributesReturnNullIfSetToNull()
    {
        $timestamps = [
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ];
        $model = new EloquentDateModelStub;
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
        $resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock('StdClass'));
        $mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
        $mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $instance = $model->newInstance($timestamps);

        $instance->created_at = null;
        $this->assertNull($instance->created_at);
    }

    public function testTimestampsAreCreatedFromStringsAndIntegers()
    {
        $model = new EloquentDateModelStub;
        $model->created_at = '2013-05-22 00:00:00';
        $this->assertInstanceOf('Carbon\Carbon', $model->created_at);

        $model = new EloquentDateModelStub;
        $model->created_at = Carbon::now()->getTimestamp();
        $this->assertInstanceOf('Carbon\Carbon', $model->created_at);

        $model = new EloquentDateModelStub;
        $model->created_at = 0;
        $this->assertInstanceOf('Carbon\Carbon', $model->created_at);

        $model = new EloquentDateModelStub;
        $model->created_at = '2012-01-01';
        $this->assertInstanceOf('Carbon\Carbon', $model->created_at);
    }

    public function testFromDateTime()
    {
        $model = new EloquentModelStub();

        $value = \Carbon\Carbon::parse('2015-04-17 22:59:01');
        $this->assertEquals('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = new DateTime('2015-04-17 22:59:01');
        $this->assertInstanceOf(DateTime::class, $value);
        $this->assertInstanceOf(DateTimeInterface::class, $value);
        $this->assertEquals('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = new DateTimeImmutable('2015-04-17 22:59:01');
        $this->assertInstanceOf(DateTimeImmutable::class, $value);
        $this->assertInstanceOf(DateTimeInterface::class, $value);
        $this->assertEquals('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = '2015-04-17 22:59:01';
        $this->assertEquals('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = '2015-04-17';
        $this->assertEquals('2015-04-17 00:00:00', $model->fromDateTime($value));

        $value = '2015-4-17';
        $this->assertEquals('2015-04-17 00:00:00', $model->fromDateTime($value));

        $value = '1429311541';
        $this->assertEquals('2015-04-17 22:59:01', $model->fromDateTime($value));
    }

    public function testInsertProcess()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');

        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('fire')->once()->with('eloquent.created: '.get_class($model), $model);
        $events->shouldReceive('fire')->once()->with('eloquent.saved: '.get_class($model), $model);

        $model->name = 'taylor';
        $model->exists = false;
        $this->assertTrue($model->save());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);

        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insert')->once()->with(['name' => 'taylor']);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');
        $model->setIncrementing(false);

        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('fire')->once()->with('eloquent.created: '.get_class($model), $model);
        $events->shouldReceive('fire')->once()->with('eloquent.saved: '.get_class($model), $model);

        $model->name = 'taylor';
        $model->exists = false;
        $this->assertTrue($model->save());
        $this->assertNull($model->id);
        $this->assertTrue($model->exists);
    }

    public function testInsertIsCancelledIfCreatingEventReturnsFalse()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(false);

        $this->assertFalse($model->save());
        $this->assertFalse($model->exists);
    }

    public function testDeleteProperlyDeletesModel()
    {
        $model = $this->getMockBuilder('Illuminate\Database\Eloquent\Model')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'touchOwners'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('id', '=', 1)->andReturn($query);
        $query->shouldReceive('delete')->once();
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('touchOwners');
        $model->exists = true;
        $model->id = 1;
        $model->delete();
    }

    public function testPushNoRelations()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
    }

    public function testPushEmptyOneRelation()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;
        $model->setRelation('relationOne', null);

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
        $this->assertNull($model->relationOne);
    }

    public function testPushOneRelation()
    {
        $related1 = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'related1'], 'id')->andReturn(2);
        $related1->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $related1->expects($this->once())->method('updateTimestamps');
        $related1->name = 'related1';
        $related1->exists = false;

        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;
        $model->setRelation('relationOne', $related1);

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
        $this->assertEquals(2, $model->relationOne->id);
        $this->assertTrue($model->relationOne->exists);
        $this->assertEquals(2, $related1->id);
        $this->assertTrue($related1->exists);
    }

    public function testPushEmptyManyRelation()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;
        $model->setRelation('relationMany', new \Illuminate\Database\Eloquent\Collection([]));

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
        $this->assertEquals(0, count($model->relationMany));
    }

    public function testPushManyRelation()
    {
        $related1 = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'related1'], 'id')->andReturn(2);
        $related1->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $related1->expects($this->once())->method('updateTimestamps');
        $related1->name = 'related1';
        $related1->exists = false;

        $related2 = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'related2'], 'id')->andReturn(3);
        $related2->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $related2->expects($this->once())->method('updateTimestamps');
        $related2->name = 'related2';
        $related2->exists = false;

        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;
        $model->setRelation('relationMany', new \Illuminate\Database\Eloquent\Collection([$related1, $related2]));

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
        $this->assertEquals(2, count($model->relationMany));
        $this->assertEquals([2, 3], $model->relationMany->pluck('id')->all());
    }

    public function testNewQueryReturnsEloquentQueryBuilder()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $grammar = m::mock('Illuminate\Database\Query\Grammars\Grammar');
        $processor = m::mock('Illuminate\Database\Query\Processors\Processor');
        $conn->shouldReceive('getQueryGrammar')->once()->andReturn($grammar);
        $conn->shouldReceive('getPostProcessor')->once()->andReturn($processor);
        EloquentModelStub::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
        $resolver->shouldReceive('connection')->andReturn($conn);
        $model = new EloquentModelStub;
        $builder = $model->newQuery();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Builder', $builder);
    }

    public function testGetAndSetTableOperations()
    {
        $model = new EloquentModelStub;
        $this->assertEquals('stub', $model->getTable());
        $model->setTable('foo');
        $this->assertEquals('foo', $model->getTable());
    }

    public function testGetKeyReturnsValueOfPrimaryKey()
    {
        $model = new EloquentModelStub;
        $model->id = 1;
        $this->assertEquals(1, $model->getKey());
        $this->assertEquals('id', $model->getKeyName());
    }

    public function testConnectionManagement()
    {
        EloquentModelStub::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
        $model = m::mock('Illuminate\Tests\Database\EloquentModelStub[getConnectionName,connection]');

        $retval = $model->setConnection('foo');
        $this->assertEquals($retval, $model);
        $this->assertEquals('foo', $model->connection);

        $model->shouldReceive('getConnectionName')->once()->andReturn('somethingElse');
        $resolver->shouldReceive('connection')->once()->with('somethingElse')->andReturn('bar');

        $this->assertEquals('bar', $model->getConnection());
    }

    public function testToArray()
    {
        $model = new EloquentModelStub;
        $model->name = 'foo';
        $model->age = null;
        $model->password = 'password1';
        $model->setHidden(['password']);
        $model->setRelation('names', new \Illuminate\Database\Eloquent\Collection([
            new EloquentModelStub(['bar' => 'baz']), new EloquentModelStub(['bam' => 'boom']),
        ]));
        $model->setRelation('partner', new EloquentModelStub(['name' => 'abby']));
        $model->setRelation('group', null);
        $model->setRelation('multi', new \Illuminate\Database\Eloquent\Collection);
        $array = $model->toArray();

        $this->assertInternalType('array', $array);
        $this->assertEquals('foo', $array['name']);
        $this->assertEquals('baz', $array['names'][0]['bar']);
        $this->assertEquals('boom', $array['names'][1]['bam']);
        $this->assertEquals('abby', $array['partner']['name']);
        $this->assertNull($array['group']);
        $this->assertEquals([], $array['multi']);
        $this->assertFalse(isset($array['password']));

        $model->setAppends(['appendable']);
        $array = $model->toArray();
        $this->assertEquals('appended', $array['appendable']);
    }

    public function testVisibleCreatesArrayWhitelist()
    {
        $model = new EloquentModelStub;
        $model->setVisible(['name']);
        $model->name = 'Taylor';
        $model->age = 26;
        $array = $model->toArray();

        $this->assertEquals(['name' => 'Taylor'], $array);
    }

    public function testHiddenCanAlsoExcludeRelationships()
    {
        $model = new EloquentModelStub;
        $model->name = 'Taylor';
        $model->setRelation('foo', ['bar']);
        $model->setHidden(['foo', 'list_items', 'password']);
        $array = $model->toArray();

        $this->assertEquals(['name' => 'Taylor'], $array);
    }

    public function testGetArrayableRelationsFunctionExcludeHiddenRelationships()
    {
        $model = new EloquentModelStub;

        $class = new ReflectionClass($model);
        $method = $class->getMethod('getArrayableRelations');
        $method->setAccessible(true);

        $model->setRelation('foo', ['bar']);
        $model->setRelation('bam', ['boom']);
        $model->setHidden(['foo']);

        $array = $method->invokeArgs($model, []);

        $this->assertSame(['bam' => ['boom']], $array);
    }

    public function testToArraySnakeAttributes()
    {
        $model = new EloquentModelStub;
        $model->setRelation('namesList', new \Illuminate\Database\Eloquent\Collection([
            new EloquentModelStub(['bar' => 'baz']), new EloquentModelStub(['bam' => 'boom']),
        ]));
        $array = $model->toArray();

        $this->assertEquals('baz', $array['names_list'][0]['bar']);
        $this->assertEquals('boom', $array['names_list'][1]['bam']);

        $model = new EloquentModelCamelStub;
        $model->setRelation('namesList', new \Illuminate\Database\Eloquent\Collection([
            new EloquentModelStub(['bar' => 'baz']), new EloquentModelStub(['bam' => 'boom']),
        ]));
        $array = $model->toArray();

        $this->assertEquals('baz', $array['namesList'][0]['bar']);
        $this->assertEquals('boom', $array['namesList'][1]['bam']);
    }

    public function testToArrayUsesMutators()
    {
        $model = new EloquentModelStub;
        $model->list_items = [1, 2, 3];
        $array = $model->toArray();

        $this->assertEquals([1, 2, 3], $array['list_items']);
    }

    public function testHidden()
    {
        $model = new EloquentModelStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $model->setHidden(['age', 'id']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testVisible()
    {
        $model = new EloquentModelStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $model->setVisible(['name', 'id']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testDynamicHidden()
    {
        $model = new EloquentModelDynamicHiddenStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testWithHidden()
    {
        $model = new EloquentModelStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $model->setHidden(['age', 'id']);
        $model->makeVisible('age');
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayNotHasKey('id', $array);
    }

    public function testMakeHidden()
    {
        $model = new EloquentModelStub(['name' => 'foo', 'age' => 'bar', 'address' => 'foobar', 'id' => 'baz']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayHasKey('address', $array);
        $this->assertArrayHasKey('id', $array);

        $array = $model->makeHidden('address')->toArray();
        $this->assertArrayNotHasKey('address', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayHasKey('id', $array);

        $array = $model->makeHidden(['name', 'age'])->toArray();
        $this->assertArrayNotHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
        $this->assertArrayNotHasKey('address', $array);
        $this->assertArrayHasKey('id', $array);
    }

    public function testDynamicVisible()
    {
        $model = new EloquentModelDynamicVisibleStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testFillable()
    {
        $model = new EloquentModelStub;
        $model->fillable(['name', 'age']);
        $model->fill(['name' => 'foo', 'age' => 'bar']);
        $this->assertEquals('foo', $model->name);
        $this->assertEquals('bar', $model->age);
    }

    public function testForceFillMethodFillsGuardedAttributes()
    {
        $model = (new EloquentModelSaveStub)->forceFill(['id' => 21]);
        $this->assertEquals(21, $model->id);
    }

    public function testFillingJSONAttributes()
    {
        $model = new EloquentModelStub;
        $model->fillable(['meta->name', 'meta->price', 'meta->size->width']);
        $model->fill(['meta->name' => 'foo', 'meta->price' => 'bar', 'meta->size->width' => 'baz']);
        $this->assertEquals(
            ['meta' => json_encode(['name' => 'foo', 'price' => 'bar', 'size' => ['width' => 'baz']])],
            $model->toArray()
        );

        $model = new EloquentModelStub(['meta' => json_encode(['name' => 'Taylor'])]);
        $model->fillable(['meta->name', 'meta->price', 'meta->size->width']);
        $model->fill(['meta->name' => 'foo', 'meta->price' => 'bar', 'meta->size->width' => 'baz']);
        $this->assertEquals(
            ['meta' => json_encode(['name' => 'foo', 'price' => 'bar', 'size' => ['width' => 'baz']])],
            $model->toArray()
        );
    }

    public function testUnguardAllowsAnythingToBeSet()
    {
        $model = new EloquentModelStub;
        EloquentModelStub::unguard();
        $model->guard(['*']);
        $model->fill(['name' => 'foo', 'age' => 'bar']);
        $this->assertEquals('foo', $model->name);
        $this->assertEquals('bar', $model->age);
        EloquentModelStub::unguard(false);
    }

    public function testUnderscorePropertiesAreNotFilled()
    {
        $model = new EloquentModelStub;
        $model->fill(['_method' => 'PUT']);
        $this->assertEquals([], $model->getAttributes());
    }

    public function testGuarded()
    {
        $model = new EloquentModelStub;
        $model->guard(['name', 'age']);
        $model->fill(['name' => 'foo', 'age' => 'bar', 'foo' => 'bar']);
        $this->assertFalse(isset($model->name));
        $this->assertFalse(isset($model->age));
        $this->assertEquals('bar', $model->foo);
    }

    public function testFillableOverridesGuarded()
    {
        $model = new EloquentModelStub;
        $model->guard(['name', 'age']);
        $model->fillable(['age', 'foo']);
        $model->fill(['name' => 'foo', 'age' => 'bar', 'foo' => 'bar']);
        $this->assertFalse(isset($model->name));
        $this->assertEquals('bar', $model->age);
        $this->assertEquals('bar', $model->foo);
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function testGlobalGuarded()
    {
        $model = new EloquentModelStub;
        $model->guard(['*']);
        $model->fill(['name' => 'foo', 'age' => 'bar', 'votes' => 'baz']);
    }

    public function testUnguardedRunsCallbackWhileBeingUnguarded()
    {
        $model = Model::unguarded(function () {
            return (new EloquentModelStub)->guard(['*'])->fill(['name' => 'Taylor']);
        });
        $this->assertEquals('Taylor', $model->name);
        $this->assertFalse(Model::isUnguarded());
    }

    public function testUnguardedCallDoesNotChangeUnguardedState()
    {
        Model::unguard();
        $model = Model::unguarded(function () {
            return (new EloquentModelStub)->guard(['*'])->fill(['name' => 'Taylor']);
        });
        $this->assertEquals('Taylor', $model->name);
        $this->assertTrue(Model::isUnguarded());
        Model::reguard();
    }

    public function testUnguardedCallDoesNotChangeUnguardedStateOnException()
    {
        try {
            Model::unguarded(function () {
                throw new Exception;
            });
        } catch (Exception $e) {
            // ignore the exception
        }
        $this->assertFalse(Model::isUnguarded());
    }

    public function testHasOneCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasOne('Illuminate\Tests\Database\EloquentModelSaveStub');
        $this->assertEquals('save_stub.eloquent_model_stub_id', $relation->getQualifiedForeignKeyName());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasOne('Illuminate\Tests\Database\EloquentModelSaveStub', 'foo');
        $this->assertEquals('save_stub.foo', $relation->getQualifiedForeignKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf('Illuminate\Tests\Database\EloquentModelSaveStub', $relation->getQuery()->getModel());
    }

    public function testMorphOneCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->morphOne('Illuminate\Tests\Database\EloquentModelSaveStub', 'morph');
        $this->assertEquals('save_stub.morph_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('save_stub.morph_type', $relation->getQualifiedMorphType());
        $this->assertEquals('Illuminate\Tests\Database\EloquentModelStub', $relation->getMorphClass());
    }

    public function testCorrectMorphClassIsReturned()
    {
        Relation::morphMap(['alias' => 'AnotherModel']);
        $model = new EloquentModelStub;

        try {
            $this->assertEquals('Illuminate\Tests\Database\EloquentModelStub', $model->getMorphClass());
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function testHasManyCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasMany('Illuminate\Tests\Database\EloquentModelSaveStub');
        $this->assertEquals('save_stub.eloquent_model_stub_id', $relation->getQualifiedForeignKeyName());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasMany('Illuminate\Tests\Database\EloquentModelSaveStub', 'foo');

        $this->assertEquals('save_stub.foo', $relation->getQualifiedForeignKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf('Illuminate\Tests\Database\EloquentModelSaveStub', $relation->getQuery()->getModel());
    }

    public function testMorphManyCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->morphMany('Illuminate\Tests\Database\EloquentModelSaveStub', 'morph');
        $this->assertEquals('save_stub.morph_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('save_stub.morph_type', $relation->getQualifiedMorphType());
        $this->assertEquals('Illuminate\Tests\Database\EloquentModelStub', $relation->getMorphClass());
    }

    public function testBelongsToCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->belongsToStub();
        $this->assertEquals('belongs_to_stub_id', $relation->getForeignKey());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf('Illuminate\Tests\Database\EloquentModelSaveStub', $relation->getQuery()->getModel());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->belongsToExplicitKeyStub();
        $this->assertEquals('foo', $relation->getForeignKey());
    }

    public function testMorphToCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        // $this->morphTo();
        $relation = $model->morphToStub();
        $this->assertEquals('morph_to_stub_id', $relation->getForeignKey());
        $this->assertEquals('morph_to_stub_type', $relation->getMorphType());
        $this->assertEquals('morphToStub', $relation->getRelation());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf('Illuminate\Tests\Database\EloquentModelSaveStub', $relation->getQuery()->getModel());

        // $this->morphTo(null, 'type', 'id');
        $relation2 = $model->morphToStubWithKeys();
        $this->assertEquals('id', $relation2->getForeignKey());
        $this->assertEquals('type', $relation2->getMorphType());
        $this->assertEquals('morphToStubWithKeys', $relation2->getRelation());

        // $this->morphTo('someName');
        $relation3 = $model->morphToStubWithName();
        $this->assertEquals('some_name_id', $relation3->getForeignKey());
        $this->assertEquals('some_name_type', $relation3->getMorphType());
        $this->assertEquals('someName', $relation3->getRelation());

        // $this->morphTo('someName', 'type', 'id');
        $relation4 = $model->morphToStubWithNameAndKeys();
        $this->assertEquals('id', $relation4->getForeignKey());
        $this->assertEquals('type', $relation4->getMorphType());
        $this->assertEquals('someName', $relation4->getRelation());
    }

    public function testBelongsToManyCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        $relation = $model->belongsToMany('Illuminate\Tests\Database\EloquentModelSaveStub');
        $this->assertEquals('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_stub_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_save_stub_id', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf('Illuminate\Tests\Database\EloquentModelSaveStub', $relation->getQuery()->getModel());
        $this->assertEquals(__FUNCTION__, $relation->getRelationName());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->belongsToMany('Illuminate\Tests\Database\EloquentModelSaveStub', 'table', 'foreign', 'other');
        $this->assertEquals('table.foreign', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('table.other', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf('Illuminate\Tests\Database\EloquentModelSaveStub', $relation->getQuery()->getModel());
    }

    public function testRelationsWithVariedConnections()
    {
        // Has one
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasOne('Illuminate\Tests\Database\EloquentNoConnectionModelStub');
        $this->assertEquals('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasOne('Illuminate\Tests\Database\EloquentDifferentConnectionModelStub');
        $this->assertEquals('different_connection', $relation->getRelated()->getConnectionName());

        // Morph One
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->morphOne('Illuminate\Tests\Database\EloquentNoConnectionModelStub', 'type');
        $this->assertEquals('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->morphOne('Illuminate\Tests\Database\EloquentDifferentConnectionModelStub', 'type');
        $this->assertEquals('different_connection', $relation->getRelated()->getConnectionName());

        // Belongs to
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsTo('Illuminate\Tests\Database\EloquentNoConnectionModelStub');
        $this->assertEquals('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsTo('Illuminate\Tests\Database\EloquentDifferentConnectionModelStub');
        $this->assertEquals('different_connection', $relation->getRelated()->getConnectionName());

        // has many
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasMany('Illuminate\Tests\Database\EloquentNoConnectionModelStub');
        $this->assertEquals('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasMany('Illuminate\Tests\Database\EloquentDifferentConnectionModelStub');
        $this->assertEquals('different_connection', $relation->getRelated()->getConnectionName());

        // has many through
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasManyThrough('Illuminate\Tests\Database\EloquentNoConnectionModelStub', 'Illuminate\Tests\Database\EloquentModelSaveStub');
        $this->assertEquals('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasManyThrough('Illuminate\Tests\Database\EloquentDifferentConnectionModelStub', 'Illuminate\Tests\Database\EloquentModelSaveStub');
        $this->assertEquals('different_connection', $relation->getRelated()->getConnectionName());

        // belongs to many
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsToMany('Illuminate\Tests\Database\EloquentNoConnectionModelStub');
        $this->assertEquals('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsToMany('Illuminate\Tests\Database\EloquentDifferentConnectionModelStub');
        $this->assertEquals('different_connection', $relation->getRelated()->getConnectionName());
    }

    public function testModelsAssumeTheirName()
    {
        require_once __DIR__.'/stubs/EloquentModelNamespacedStub.php';

        $model = new EloquentModelWithoutTableStub;
        $this->assertEquals('eloquent_model_without_table_stubs', $model->getTable());

        $namespacedModel = new \Foo\Bar\EloquentModelNamespacedStub;
        $this->assertEquals('eloquent_model_namespaced_stubs', $namespacedModel->getTable());
    }

    public function testTheMutatorCacheIsPopulated()
    {
        $class = new EloquentModelStub;

        $expectedAttributes = [
            'list_items',
            'password',
            'appendable',
        ];

        $this->assertEquals($expectedAttributes, $class->getMutatedAttributes());
    }

    public function testRouteKeyIsPrimaryKey()
    {
        $model = new EloquentModelNonIncrementingStub;
        $model->id = 'foo';
        $this->assertEquals('foo', $model->getRouteKey());
    }

    public function testRouteNameIsPrimaryKeyName()
    {
        $model = new EloquentModelStub;
        $this->assertEquals('id', $model->getRouteKeyName());
    }

    public function testCloneModelMakesAFreshCopyOfTheModel()
    {
        $class = new EloquentModelStub;
        $class->id = 1;
        $class->exists = true;
        $class->first = 'taylor';
        $class->last = 'otwell';
        $class->created_at = $class->freshTimestamp();
        $class->updated_at = $class->freshTimestamp();
        $class->setRelation('foo', ['bar']);

        $clone = $class->replicate();

        $this->assertNull($clone->id);
        $this->assertFalse($clone->exists);
        $this->assertEquals('taylor', $clone->first);
        $this->assertEquals('otwell', $clone->last);
        $this->assertObjectNotHasAttribute('created_at', $clone);
        $this->assertObjectNotHasAttribute('updated_at', $clone);
        $this->assertEquals(['bar'], $clone->foo);
    }

    public function testModelObserversCanBeAttachedToModels()
    {
        EloquentModelStub::setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelStub', 'Illuminate\Tests\Database\EloquentTestObserverStub@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelStub', 'Illuminate\Tests\Database\EloquentTestObserverStub@saved');
        $events->shouldReceive('forget');
        EloquentModelStub::observe(new EloquentTestObserverStub);
        EloquentModelStub::flushEventListeners();
    }

    public function testModelObserversCanBeAttachedToModelsWithString()
    {
        EloquentModelStub::setEventDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelStub', 'Illuminate\Tests\Database\EloquentTestObserverStub@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelStub', 'Illuminate\Tests\Database\EloquentTestObserverStub@saved');
        $events->shouldReceive('forget');
        EloquentModelStub::observe('Illuminate\Tests\Database\EloquentTestObserverStub');
        EloquentModelStub::flushEventListeners();
    }

    public function testSetObservableEvents()
    {
        $class = new EloquentModelStub;
        $class->setObservableEvents(['foo']);

        $this->assertContains('foo', $class->getObservableEvents());
    }

    public function testAddObservableEvent()
    {
        $class = new EloquentModelStub;
        $class->addObservableEvents('foo');

        $this->assertContains('foo', $class->getObservableEvents());
    }

    public function testAddMultipleObserveableEvents()
    {
        $class = new EloquentModelStub;
        $class->addObservableEvents('foo', 'bar');

        $this->assertContains('foo', $class->getObservableEvents());
        $this->assertContains('bar', $class->getObservableEvents());
    }

    public function testRemoveObservableEvent()
    {
        $class = new EloquentModelStub;
        $class->setObservableEvents(['foo', 'bar']);
        $class->removeObservableEvents('bar');

        $this->assertNotContains('bar', $class->getObservableEvents());
    }

    public function testRemoveMultipleObservableEvents()
    {
        $class = new EloquentModelStub;
        $class->setObservableEvents(['foo', 'bar']);
        $class->removeObservableEvents('foo', 'bar');

        $this->assertNotContains('foo', $class->getObservableEvents());
        $this->assertNotContains('bar', $class->getObservableEvents());
    }

    /**
     * @expectedException LogicException
     */
    public function testGetModelAttributeMethodThrowsExceptionIfNotRelation()
    {
        $model = new EloquentModelStub;
        $relation = $model->incorrectRelationStub;
    }

    public function testModelIsBootedOnUnserialize()
    {
        $model = new EloquentModelBootingTestStub;
        $this->assertTrue(EloquentModelBootingTestStub::isBooted());
        $model->foo = 'bar';
        $string = serialize($model);
        $model = null;
        EloquentModelBootingTestStub::unboot();
        $this->assertFalse(EloquentModelBootingTestStub::isBooted());
        $model = unserialize($string);
        $this->assertTrue(EloquentModelBootingTestStub::isBooted());
    }

    public function testAppendingOfAttributes()
    {
        $model = new EloquentModelAppendsStub;

        $this->assertTrue(isset($model->is_admin));
        $this->assertTrue(isset($model->camelCased));
        $this->assertTrue(isset($model->StudlyCased));

        $this->assertEquals('admin', $model->is_admin);
        $this->assertEquals('camelCased', $model->camelCased);
        $this->assertEquals('StudlyCased', $model->StudlyCased);

        $model->setHidden(['is_admin', 'camelCased', 'StudlyCased']);
        $this->assertEquals([], $model->toArray());

        $model->setVisible([]);
        $this->assertEquals([], $model->toArray());
    }

    public function testGetMutatedAttributes()
    {
        $model = new EloquentModelGetMutatorsStub;

        $this->assertEquals(['first_name', 'middle_name', 'last_name'], $model->getMutatedAttributes());

        EloquentModelGetMutatorsStub::resetMutatorCache();

        EloquentModelGetMutatorsStub::$snakeAttributes = false;
        $this->assertEquals(['firstName', 'middleName', 'lastName'], $model->getMutatedAttributes());
    }

    public function testReplicateCreatesANewModelInstanceWithSameAttributeValues()
    {
        $model = new EloquentModelStub;
        $model->id = 'id';
        $model->foo = 'bar';
        $model->created_at = new DateTime;
        $model->updated_at = new DateTime;
        $replicated = $model->replicate();

        $this->assertNull($replicated->id);
        $this->assertEquals('bar', $replicated->foo);
        $this->assertNull($replicated->created_at);
        $this->assertNull($replicated->updated_at);
    }

    public function testIncrementOnExistingModelCallsQueryAndSetsAttribute()
    {
        $model = m::mock('Illuminate\Tests\Database\EloquentModelStub[newQuery]');
        $model->exists = true;
        $model->id = 1;
        $model->syncOriginalAttribute('id');
        $model->foo = 2;

        $model->shouldReceive('newQuery')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('where')->andReturn($query);
        $query->shouldReceive('increment');

        $model->publicIncrement('foo');

        $this->assertEquals(3, $model->foo);
        $this->assertFalse($model->isDirty());
    }

    public function testRelationshipTouchOwnersIsPropagated()
    {
        $relation = $this->getMockBuilder('Illuminate\Database\Eloquent\Relations\BelongsTo')->setMethods(['touch'])->disableOriginalConstructor()->getMock();
        $relation->expects($this->once())->method('touch');

        $model = m::mock('Illuminate\Tests\Database\EloquentModelStub[partner]');
        $this->addMockConnection($model);
        $model->shouldReceive('partner')->once()->andReturn($relation);
        $model->setTouchedRelations(['partner']);

        $mockPartnerModel = m::mock('Illuminate\Tests\Database\EloquentModelStub[touchOwners]');
        $mockPartnerModel->shouldReceive('touchOwners')->once();
        $model->setRelation('partner', $mockPartnerModel);

        $model->touchOwners();
    }

    public function testRelationshipTouchOwnersIsNotPropagatedIfNoRelationshipResult()
    {
        $relation = $this->getMockBuilder('Illuminate\Database\Eloquent\Relations\BelongsTo')->setMethods(['touch'])->disableOriginalConstructor()->getMock();
        $relation->expects($this->once())->method('touch');

        $model = m::mock('Illuminate\Tests\Database\EloquentModelStub[partner]');
        $this->addMockConnection($model);
        $model->shouldReceive('partner')->once()->andReturn($relation);
        $model->setTouchedRelations(['partner']);

        $model->setRelation('partner', null);

        $model->touchOwners();
    }

    public function testModelAttributesAreCastedWhenPresentInCastsArray()
    {
        $model = new EloquentModelCastingStub;
        $model->setDateFormat('Y-m-d H:i:s');
        $model->intAttribute = '3';
        $model->floatAttribute = '4.0';
        $model->stringAttribute = 2.5;
        $model->boolAttribute = 1;
        $model->booleanAttribute = 0;
        $model->objectAttribute = ['foo' => 'bar'];
        $obj = new StdClass;
        $obj->foo = 'bar';
        $model->arrayAttribute = $obj;
        $model->jsonAttribute = ['foo' => 'bar'];
        $model->dateAttribute = '1969-07-20';
        $model->datetimeAttribute = '1969-07-20 22:56:00';
        $model->timestampAttribute = '1969-07-20 22:56:00';

        $this->assertInternalType('int', $model->intAttribute);
        $this->assertInternalType('float', $model->floatAttribute);
        $this->assertInternalType('string', $model->stringAttribute);
        $this->assertInternalType('boolean', $model->boolAttribute);
        $this->assertInternalType('boolean', $model->booleanAttribute);
        $this->assertInternalType('object', $model->objectAttribute);
        $this->assertInternalType('array', $model->arrayAttribute);
        $this->assertInternalType('array', $model->jsonAttribute);
        $this->assertTrue($model->boolAttribute);
        $this->assertFalse($model->booleanAttribute);
        $this->assertEquals($obj, $model->objectAttribute);
        $this->assertEquals(['foo' => 'bar'], $model->arrayAttribute);
        $this->assertEquals(['foo' => 'bar'], $model->jsonAttribute);
        $this->assertEquals('{"foo":"bar"}', $model->jsonAttributeValue());
        $this->assertInstanceOf('Carbon\Carbon', $model->dateAttribute);
        $this->assertInstanceOf('Carbon\Carbon', $model->datetimeAttribute);
        $this->assertEquals('1969-07-20', $model->dateAttribute->toDateString());
        $this->assertEquals('1969-07-20 22:56:00', $model->datetimeAttribute->toDateTimeString());
        $this->assertEquals(-14173440, $model->timestampAttribute);

        $arr = $model->toArray();
        $this->assertInternalType('int', $arr['intAttribute']);
        $this->assertInternalType('float', $arr['floatAttribute']);
        $this->assertInternalType('string', $arr['stringAttribute']);
        $this->assertInternalType('boolean', $arr['boolAttribute']);
        $this->assertInternalType('boolean', $arr['booleanAttribute']);
        $this->assertInternalType('object', $arr['objectAttribute']);
        $this->assertInternalType('array', $arr['arrayAttribute']);
        $this->assertInternalType('array', $arr['jsonAttribute']);
        $this->assertTrue($arr['boolAttribute']);
        $this->assertFalse($arr['booleanAttribute']);
        $this->assertEquals($obj, $arr['objectAttribute']);
        $this->assertEquals(['foo' => 'bar'], $arr['arrayAttribute']);
        $this->assertEquals(['foo' => 'bar'], $arr['jsonAttribute']);
        $this->assertEquals('1969-07-20 00:00:00', $arr['dateAttribute']);
        $this->assertEquals('1969-07-20 22:56:00', $arr['datetimeAttribute']);
        $this->assertEquals(-14173440, $arr['timestampAttribute']);
    }

    public function testModelDateAttributeCastingResetsTime()
    {
        $model = new EloquentModelCastingStub;
        $model->setDateFormat('Y-m-d H:i:s');
        $model->dateAttribute = '1969-07-20 22:56:00';

        $this->assertEquals('1969-07-20 00:00:00', $model->dateAttribute->toDateTimeString());

        $arr = $model->toArray();
        $this->assertEquals('1969-07-20 00:00:00', $arr['dateAttribute']);
    }

    public function testModelAttributeCastingPreservesNull()
    {
        $model = new EloquentModelCastingStub;
        $model->intAttribute = null;
        $model->floatAttribute = null;
        $model->stringAttribute = null;
        $model->boolAttribute = null;
        $model->booleanAttribute = null;
        $model->objectAttribute = null;
        $model->arrayAttribute = null;
        $model->jsonAttribute = null;
        $model->dateAttribute = null;
        $model->datetimeAttribute = null;
        $model->timestampAttribute = null;

        $attributes = $model->getAttributes();

        $this->assertNull($attributes['intAttribute']);
        $this->assertNull($attributes['floatAttribute']);
        $this->assertNull($attributes['stringAttribute']);
        $this->assertNull($attributes['boolAttribute']);
        $this->assertNull($attributes['booleanAttribute']);
        $this->assertNull($attributes['objectAttribute']);
        $this->assertNull($attributes['arrayAttribute']);
        $this->assertNull($attributes['jsonAttribute']);
        $this->assertNull($attributes['dateAttribute']);
        $this->assertNull($attributes['datetimeAttribute']);
        $this->assertNull($attributes['timestampAttribute']);

        $this->assertNull($model->intAttribute);
        $this->assertNull($model->floatAttribute);
        $this->assertNull($model->stringAttribute);
        $this->assertNull($model->boolAttribute);
        $this->assertNull($model->booleanAttribute);
        $this->assertNull($model->objectAttribute);
        $this->assertNull($model->arrayAttribute);
        $this->assertNull($model->jsonAttribute);
        $this->assertNull($model->dateAttribute);
        $this->assertNull($model->datetimeAttribute);
        $this->assertNull($model->timestampAttribute);

        $array = $model->toArray();

        $this->assertNull($array['intAttribute']);
        $this->assertNull($array['floatAttribute']);
        $this->assertNull($array['stringAttribute']);
        $this->assertNull($array['boolAttribute']);
        $this->assertNull($array['booleanAttribute']);
        $this->assertNull($array['objectAttribute']);
        $this->assertNull($array['arrayAttribute']);
        $this->assertNull($array['jsonAttribute']);
        $this->assertNull($array['dateAttribute']);
        $this->assertNull($array['datetimeAttribute']);
        $this->assertNull($array['timestampAttribute']);
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function testModelAttributeCastingFailsOnUnencodableData()
    {
        $model = new EloquentModelCastingStub;
        $model->objectAttribute = ['foo' => "b\xF8r"];
        $obj = new StdClass;
        $obj->foo = "b\xF8r";
        $model->arrayAttribute = $obj;
        $model->jsonAttribute = ['foo' => "b\xF8r"];

        $model->getAttributes();
    }

    public function testUpdatingNonExistentModelFails()
    {
        $model = new EloquentModelStub;
        $this->assertFalse($model->update());
    }

    public function testIssetBehavesCorrectlyWithAttributesAndRelationships()
    {
        $model = new EloquentModelStub;
        $this->assertFalse(isset($model->nonexistent));

        $model->some_attribute = 'some_value';
        $this->assertTrue(isset($model->some_attribute));

        $model->setRelation('some_relation', 'some_value');
        $this->assertTrue(isset($model->some_relation));
    }

    public function testNonExistingAttributeWithInternalMethodNameDoesntCallMethod()
    {
        $model = m::mock('Illuminate\Tests\Database\EloquentModelStub[delete,getRelationValue]');
        $model->name = 'Spark';
        $model->shouldNotReceive('delete');
        $model->shouldReceive('getRelationValue')->once()->with('belongsToStub')->andReturn('relation');

        // Can return a normal relation
        $this->assertEquals('relation', $model->belongsToStub);

        // Can return a normal attribute
        $this->assertEquals('Spark', $model->name);

        // Returns null for a Model.php method name
        $this->assertNull($model->delete);

        $model = m::mock('Illuminate\Tests\Database\EloquentModelStub[delete]');
        $model->delete = 123;
        $this->assertEquals(123, $model->delete);
    }

    public function testIntKeyTypePreserved()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with([], 'id')->andReturn(1);
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));

        $this->assertTrue($model->save());
        $this->assertEquals(1, $model->id);
    }

    public function testStringKeyTypePreserved()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentKeyTypeModelStub')->setMethods(['newQueryWithoutScopes', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('insertGetId')->once()->with([], 'id')->andReturn('string id');
        $model->expects($this->once())->method('newQueryWithoutScopes')->will($this->returnValue($query));

        $this->assertTrue($model->save());
        $this->assertEquals('string id', $model->id);
    }

    public function testScopesMethod()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        $scopes = [
            'published',
            'category' => 'Laravel',
            'framework' => ['Laravel', '5.3'],
        ];

        $this->assertInstanceOf(Builder::class, $model->scopes($scopes));

        $this->assertSame($scopes, $model->scopesCalled);
    }

    public function testIsWithNull()
    {
        $firstInstance = new EloquentModelStub(['id' => 1]);
        $secondInstance = null;

        $this->assertFalse($firstInstance->is($secondInstance));
    }

    public function testIsWithTheSameModelInstance()
    {
        $firstInstance = new EloquentModelStub(['id' => 1]);
        $secondInstance = new EloquentModelStub(['id' => 1]);
        $result = $firstInstance->is($secondInstance);
        $this->assertTrue($result);
    }

    public function testIsWithAnotherModelInstance()
    {
        $firstInstance = new EloquentModelStub(['id' => 1]);
        $secondInstance = new EloquentModelStub(['id' => 2]);
        $result = $firstInstance->is($secondInstance);
        $this->assertFalse($result);
    }

    public function testIsWithAnotherTable()
    {
        $firstInstance = new EloquentModelStub(['id' => 1]);
        $secondInstance = new EloquentModelStub(['id' => 1]);
        $secondInstance->setTable('foo');
        $result = $firstInstance->is($secondInstance);
        $this->assertFalse($result);
    }

    public function testIsWithAnotherConnection()
    {
        $firstInstance = new EloquentModelStub(['id' => 1]);
        $secondInstance = new EloquentModelStub(['id' => 1]);
        $secondInstance->setConnection('foo');
        $result = $firstInstance->is($secondInstance);
        $this->assertFalse($result);
    }

    protected function addMockConnection($model)
    {
        $model->setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
        $resolver->shouldReceive('connection')->andReturn(m::mock('Illuminate\Database\Connection'));
        $model->getConnection()->shouldReceive('getQueryGrammar')->andReturn(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
        $model->getConnection()->shouldReceive('getPostProcessor')->andReturn(m::mock('Illuminate\Database\Query\Processors\Processor'));
    }
}

class EloquentTestObserverStub
{
    public function creating()
    {
    }

    public function saved()
    {
    }
}

class EloquentModelStub extends Model
{
    public $connection;
    public $scopesCalled = [];
    protected $table = 'stub';
    protected $guarded = [];
    protected $morph_to_stub_type = 'Illuminate\Tests\Database\EloquentModelSaveStub';

    public function getListItemsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setListItemsAttribute($value)
    {
        $this->attributes['list_items'] = json_encode($value);
    }

    public function getPasswordAttribute()
    {
        return '******';
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = sha1($value);
    }

    public function publicIncrement($column, $amount = 1)
    {
        return $this->increment($column, $amount);
    }

    public function belongsToStub()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentModelSaveStub');
    }

    public function morphToStub()
    {
        return $this->morphTo();
    }

    public function morphToStubWithKeys()
    {
        return $this->morphTo(null, 'type', 'id');
    }

    public function morphToStubWithName()
    {
        return $this->morphTo('someName');
    }

    public function morphToStubWithNameAndKeys()
    {
        return $this->morphTo('someName', 'type', 'id');
    }

    public function belongsToExplicitKeyStub()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentModelSaveStub', 'foo');
    }

    public function incorrectRelationStub()
    {
        return 'foo';
    }

    public function getDates()
    {
        return [];
    }

    public function getAppendableAttribute()
    {
        return 'appended';
    }

    public function scopePublished(Builder $builder)
    {
        $this->scopesCalled[] = 'published';
    }

    public function scopeCategory(Builder $builder, $category)
    {
        $this->scopesCalled['category'] = $category;
    }

    public function scopeFramework(Builder $builder, $framework, $version)
    {
        $this->scopesCalled['framework'] = [$framework, $version];
    }
}

class EloquentModelCamelStub extends EloquentModelStub
{
    public static $snakeAttributes = false;
}

class EloquentDateModelStub extends EloquentModelStub
{
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }
}

class EloquentModelSaveStub extends Model
{
    protected $table = 'save_stub';
    protected $guarded = ['id'];

    public function save(array $options = [])
    {
        $_SERVER['__eloquent.saved'] = true;
    }

    public function setIncrementing($value)
    {
        $this->incrementing = $value;
    }
}

class EloquentKeyTypeModelStub extends EloquentModelStub
{
    protected $keyType = 'string';
}

class EloquentModelFindWithWritePdoStub extends Model
{
    public function newQuery()
    {
        $mock = m::mock('Illuminate\Database\Eloquent\Builder');
        $mock->shouldReceive('useWritePdo')->once()->andReturnSelf();
        $mock->shouldReceive('find')->once()->with(1)->andReturn('foo');

        return $mock;
    }
}

class EloquentModelDestroyStub extends Model
{
    public function newQuery()
    {
        $mock = m::mock('Illuminate\Database\Eloquent\Builder');
        $mock->shouldReceive('whereIn')->once()->with('id', [1, 2, 3])->andReturn($mock);
        $mock->shouldReceive('get')->once()->andReturn([$model = m::mock('StdClass')]);
        $model->shouldReceive('delete')->once();

        return $mock;
    }
}

class EloquentModelHydrateRawStub extends Model
{
    public static function hydrate(array $items, $connection = null)
    {
        return 'hydrated';
    }

    public function getConnection()
    {
        $mock = m::mock('Illuminate\Database\Connection');
        $mock->shouldReceive('select')->once()->with('SELECT ?', ['foo'])->andReturn([]);

        return $mock;
    }
}

class EloquentModelWithStub extends Model
{
    public function newQuery()
    {
        $mock = m::mock('Illuminate\Database\Eloquent\Builder');
        $mock->shouldReceive('with')->once()->with(['foo', 'bar'])->andReturn('foo');

        return $mock;
    }
}

class EloquentModelWithoutRelationStub extends Model
{
    public $with = ['foo'];

    protected $guarded = [];

    public function getEagerLoads()
    {
        return $this->eagerLoads;
    }
}

class EloquentModelWithoutTableStub extends Model
{
}

class EloquentModelBootingTestStub extends Model
{
    public static function unboot()
    {
        unset(static::$booted[static::class]);
    }

    public static function isBooted()
    {
        return array_key_exists(static::class, static::$booted);
    }
}

class EloquentModelAppendsStub extends Model
{
    protected $appends = ['is_admin', 'camelCased', 'StudlyCased'];

    public function getIsAdminAttribute()
    {
        return 'admin';
    }

    public function getCamelCasedAttribute()
    {
        return 'camelCased';
    }

    public function getStudlyCasedAttribute()
    {
        return 'StudlyCased';
    }
}

class EloquentModelGetMutatorsStub extends Model
{
    public static function resetMutatorCache()
    {
        static::$mutatorCache = [];
    }

    public function getFirstNameAttribute()
    {
    }

    public function getMiddleNameAttribute()
    {
    }

    public function getLastNameAttribute()
    {
    }

    public function doNotgetFirstInvalidAttribute()
    {
    }

    public function doNotGetSecondInvalidAttribute()
    {
    }

    public function doNotgetThirdInvalidAttributeEither()
    {
    }

    public function doNotGetFourthInvalidAttributeEither()
    {
    }
}

class EloquentModelCastingStub extends Model
{
    protected $casts = [
        'intAttribute' => 'int',
        'floatAttribute' => 'float',
        'stringAttribute' => 'string',
        'boolAttribute' => 'bool',
        'booleanAttribute' => 'boolean',
        'objectAttribute' => 'object',
        'arrayAttribute' => 'array',
        'jsonAttribute' => 'json',
        'dateAttribute' => 'date',
        'datetimeAttribute' => 'datetime',
        'timestampAttribute' => 'timestamp',
    ];

    public function jsonAttributeValue()
    {
        return $this->attributes['jsonAttribute'];
    }
}

class EloquentModelDynamicHiddenStub extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stub';
    protected $guarded = [];

    public function getHidden()
    {
        return ['age', 'id'];
    }
}

class EloquentModelDynamicVisibleStub extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stub';
    protected $guarded = [];

    public function getVisible()
    {
        return ['name', 'id'];
    }
}

class EloquentModelNonIncrementingStub extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stub';
    protected $guarded = [];
    public $incrementing = false;
}

class EloquentNoConnectionModelStub extends EloquentModelStub
{
}

class EloquentDifferentConnectionModelStub extends EloquentModelStub
{
    public $connection = 'different_connection';
}

class EloquentModelSavingEventStub
{
}

class EloquentModelEventObjectStub extends \Illuminate\Database\Eloquent\Model
{
    protected $dispatchesEvents = [
        'saving' => EloquentModelSavingEventStub::class,
    ];
}
