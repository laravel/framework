<?php

namespace Illuminate\Tests\Database;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Foo\Bar\EloquentModelNamespacedStub;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

include_once 'Enums.php';

class DatabaseEloquentModelTest extends TestCase
{
    use InteractsWithTime;

    protected $encrypter;

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
        Carbon::setTestNow(null);

        Model::unsetEventDispatcher();
        Carbon::resetToStringFormat();
    }

    public function testAttributeManipulation()
    {
        $model = new EloquentModelStub;
        $model->name = 'foo';
        $this->assertSame('foo', $model->name);
        $this->assertTrue(isset($model->name));
        unset($model->name);
        $this->assertFalse(isset($model->name));

        // test mutation
        $model->list_items = ['name' => 'taylor'];
        $this->assertEquals(['name' => 'taylor'], $model->list_items);
        $attributes = $model->getAttributes();
        $this->assertSame(json_encode(['name' => 'taylor']), $attributes['list_items']);
    }

    public function testSetAttributeWithNumericKey()
    {
        $model = new EloquentDateModelStub;
        $model->setAttribute(0, 'value');

        $this->assertEquals([0 => 'value'], $model->getAttributes());
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

    public function testIntAndNullComparisonWhenDirty()
    {
        $model = new EloquentModelCastingStub;
        $model->intAttribute = null;
        $model->syncOriginal();
        $this->assertFalse($model->isDirty('intAttribute'));
        $model->forceFill(['intAttribute' => 0]);
        $this->assertTrue($model->isDirty('intAttribute'));
    }

    public function testFloatAndNullComparisonWhenDirty()
    {
        $model = new EloquentModelCastingStub;
        $model->floatAttribute = null;
        $model->syncOriginal();
        $this->assertFalse($model->isDirty('floatAttribute'));
        $model->forceFill(['floatAttribute' => 0.0]);
        $this->assertTrue($model->isDirty('floatAttribute'));
    }

    public function testDirtyOnCastOrDateAttributes()
    {
        $model = new EloquentModelCastingStub;
        $model->setDateFormat('Y-m-d H:i:s');
        $model->boolAttribute = 1;
        $model->foo = 1;
        $model->bar = '2017-03-18';
        $model->dateAttribute = '2017-03-18';
        $model->datetimeAttribute = '2017-03-23 22:17:00';
        $model->syncOriginal();

        $model->boolAttribute = true;
        $model->foo = true;
        $model->bar = '2017-03-18 00:00:00';
        $model->dateAttribute = '2017-03-18 00:00:00';
        $model->datetimeAttribute = null;

        $this->assertTrue($model->isDirty());
        $this->assertTrue($model->isDirty('foo'));
        $this->assertTrue($model->isDirty('bar'));
        $this->assertFalse($model->isDirty('boolAttribute'));
        $this->assertFalse($model->isDirty('dateAttribute'));
        $this->assertTrue($model->isDirty('datetimeAttribute'));
    }

    public function testDirtyOnCastedObjects()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'objectAttribute' => '["one", "two", "three"]',
            'collectionAttribute' => '["one", "two", "three"]',
        ]);
        $model->syncOriginal();

        $model->objectAttribute = ['one', 'two', 'three'];
        $model->collectionAttribute = ['one', 'two', 'three'];

        $this->assertFalse($model->isDirty());
        $this->assertFalse($model->isDirty('objectAttribute'));
        $this->assertFalse($model->isDirty('collectionAttribute'));
    }

    public function testDirtyOnCastedArrayObject()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asarrayobjectAttribute' => '{"foo": "bar"}',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(ArrayObject::class, $model->asarrayobjectAttribute);
        $this->assertFalse($model->isDirty('asarrayobjectAttribute'));

        $model->asarrayobjectAttribute = ['foo' => 'bar'];
        $this->assertFalse($model->isDirty('asarrayobjectAttribute'));

        $model->asarrayobjectAttribute = ['foo' => 'baz'];
        $this->assertTrue($model->isDirty('asarrayobjectAttribute'));
    }

    public function testDirtyOnCastedCollection()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'ascollectionAttribute' => '{"foo": "bar"}',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(BaseCollection::class, $model->ascollectionAttribute);
        $this->assertFalse($model->isDirty('ascollectionAttribute'));

        $model->ascollectionAttribute = ['foo' => 'bar'];
        $this->assertFalse($model->isDirty('ascollectionAttribute'));

        $model->ascollectionAttribute = ['foo' => 'baz'];
        $this->assertTrue($model->isDirty('ascollectionAttribute'));
    }

    public function testDirtyOnCastedCustomCollection()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asCustomCollectionAttribute' => '{"bar": "foo"}',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(CustomCollection::class, $model->asCustomCollectionAttribute);
        $this->assertFalse($model->isDirty('asCustomCollectionAttribute'));

        $model->asCustomCollectionAttribute = ['bar' => 'foo'];
        $this->assertFalse($model->isDirty('asCustomCollectionAttribute'));

        $model->asCustomCollectionAttribute = ['baz' => 'foo'];
        $this->assertTrue($model->isDirty('asCustomCollectionAttribute'));
    }

    public function testDirtyOnCastedCustomCollectionAsArray()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asCustomCollectionAsArrayAttribute' => '{"bar": "foo"}',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(CustomCollection::class, $model->asCustomCollectionAsArrayAttribute);
        $this->assertFalse($model->isDirty('asCustomCollectionAsArrayAttribute'));

        $model->asCustomCollectionAsArrayAttribute = ['bar' => 'foo'];
        $this->assertFalse($model->isDirty('asCustomCollectionAsArrayAttribute'));

        $model->asCustomCollectionAsArrayAttribute = ['baz' => 'foo'];
        $this->assertTrue($model->isDirty('asCustomCollectionAsArrayAttribute'));
    }

    public function testDirtyOnCastedStringable()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asStringableAttribute' => 'foo bar',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(Stringable::class, $model->asStringableAttribute);
        $this->assertFalse($model->isDirty('asStringableAttribute'));

        $model->asStringableAttribute = new Stringable('foo bar');
        $this->assertFalse($model->isDirty('asStringableAttribute'));

        $model->asStringableAttribute = new Stringable('foo baz');
        $this->assertTrue($model->isDirty('asStringableAttribute'));
    }

    // public function testDirtyOnCastedEncryptedCollection()
    // {
    //     $this->encrypter = m::mock(Encrypter::class);
    //     Crypt::swap($this->encrypter);
    //     Model::$encrypter = null;

    //     $this->encrypter->expects('encryptString')
    //         ->with('{"foo":"bar"}')
    //         ->andReturn('encrypted-value');

    //     $this->encrypter->expects('decryptString')
    //         ->with('encrypted-value')
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('encryptString')
    //         ->with('{"foo":"baz"}')
    //         ->andReturn('new-encrypted-value');

    //     $this->encrypter->expects('decrypt')
    //         ->with('encrypted-value', false)
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('decrypt')
    //         ->with('new-encrypted-value', false)
    //         ->andReturn('{"foo":"baz"}');

    //     $model = new EloquentModelCastingStub;
    //     $model->setRawAttributes([
    //         'asEncryptedCollectionAttribute' => 'encrypted-value',
    //     ]);
    //     $model->syncOriginal();

    //     $this->assertInstanceOf(BaseCollection::class, $model->asEncryptedCollectionAttribute);
    //     $this->assertFalse($model->isDirty('asEncryptedCollectionAttribute'));

    //     $model->asEncryptedCollectionAttribute = ['foo' => 'bar'];
    //     $this->assertFalse($model->isDirty('asEncryptedCollectionAttribute'));

    //     $model->asEncryptedCollectionAttribute = ['foo' => 'baz'];
    //     $this->assertTrue($model->isDirty('asEncryptedCollectionAttribute'));
    // }

    // public function testDirtyOnCastedEncryptedCustomCollection()
    // {
    //     $this->encrypter = m::mock(Encrypter::class);
    //     Crypt::swap($this->encrypter);
    //     Model::$encrypter = null;

    //     $this->encrypter->expects('encryptString')
    //         ->twice()
    //         ->with('{"foo":"bar"}')
    //         ->andReturn('encrypted-custom-value');

    //     $this->encrypter->expects('decryptString')
    //         ->with('encrypted-custom-value')
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('encryptString')
    //         ->with('{"foo":"baz"}')
    //         ->andReturn('new-encrypted-custom-value');

    //     $this->encrypter->expects('decrypt')
    //         ->with('encrypted-custom-value', false)
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('decrypt')
    //         ->with('new-encrypted-custom-value', false)
    //         ->andReturn('{"foo":"baz"}');

    //     $model = new EloquentModelCastingStub;
    //     $model->setRawAttributes([
    //         'asEncryptedCustomCollectionAttribute' => 'encrypted-custom-value',
    //     ]);
    //     $model->syncOriginal();

    //     $this->assertInstanceOf(CustomCollection::class, $model->asEncryptedCustomCollectionAttribute);
    //     $this->assertFalse($model->isDirty('asEncryptedCustomCollectionAttribute'));

    //     $model->asEncryptedCustomCollectionAttribute = ['foo' => 'bar'];
    //     $this->assertFalse($model->isDirty('asEncryptedCustomCollectionAttribute'));

    //     $model->asEncryptedCustomCollectionAttribute = ['foo' => 'baz'];
    //     $this->assertTrue($model->isDirty('asEncryptedCustomCollectionAttribute'));
    // }

    // public function testDirtyOnCastedEncryptedCustomCollectionAsArray()
    // {
    //     $this->encrypter = m::mock(Encrypter::class);
    //     Crypt::swap($this->encrypter);
    //     Model::$encrypter = null;

    //     $this->encrypter->expects('encryptString')
    //         ->twice()
    //         ->with('{"foo":"bar"}')
    //         ->andReturn('encrypted-custom-value');

    //     $this->encrypter->expects('decryptString')
    //         ->with('encrypted-custom-value')
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('encryptString')
    //         ->with('{"foo":"baz"}')
    //         ->andReturn('new-encrypted-custom-value');

    //     $this->encrypter->expects('decrypt')
    //         ->with('encrypted-custom-value', false)
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('decrypt')
    //         ->with('new-encrypted-custom-value', false)
    //         ->andReturn('{"foo":"baz"}');

    //     $model = new EloquentModelCastingStub;
    //     $model->setRawAttributes([
    //         'asEncryptedCustomCollectionAsArrayAttribute' => 'encrypted-custom-value',
    //     ]);
    //     $model->syncOriginal();

    //     $this->assertInstanceOf(CustomCollection::class, $model->asEncryptedCustomCollectionAsArrayAttribute);
    //     $this->assertFalse($model->isDirty('asEncryptedCustomCollectionAsArrayAttribute'));

    //     $model->asEncryptedCustomCollectionAsArrayAttribute = ['foo' => 'bar'];
    //     $this->assertFalse($model->isDirty('asEncryptedCustomCollectionAsArrayAttribute'));

    //     $model->asEncryptedCustomCollectionAsArrayAttribute = ['foo' => 'baz'];
    //     $this->assertTrue($model->isDirty('asEncryptedCustomCollectionAsArrayAttribute'));
    // }

    // public function testDirtyOnCastedEncryptedArrayObject()
    // {
    //     $this->encrypter = m::mock(Encrypter::class);
    //     Crypt::swap($this->encrypter);
    //     Model::$encrypter = null;

    //     $this->encrypter->expects('encryptString')
    //         ->twice()
    //         ->with('{"foo":"bar"}')
    //         ->andReturn('encrypted-value');

    //     $this->encrypter->expects('decryptString')
    //         ->with('encrypted-value')
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('encryptString')
    //         ->with('{"foo":"baz"}')
    //         ->andReturn('new-encrypted-value');

    //     $this->encrypter->expects('decrypt')
    //         ->with('encrypted-value', false)
    //         ->andReturn('{"foo": "bar"}');

    //     $this->encrypter->expects('decrypt')
    //         ->with('new-encrypted-value', false)
    //         ->andReturn('{"foo":"baz"}');

    //     $model = new EloquentModelCastingStub;
    //     $model->setRawAttributes([
    //         'asEncryptedArrayObjectAttribute' => 'encrypted-value',
    //     ]);
    //     $model->syncOriginal();

    //     $this->assertInstanceOf(ArrayObject::class, $model->asEncryptedArrayObjectAttribute);
    //     $this->assertFalse($model->isDirty('asEncryptedArrayObjectAttribute'));

    //     $model->asEncryptedArrayObjectAttribute = ['foo' => 'bar'];
    //     $this->assertFalse($model->isDirty('asEncryptedArrayObjectAttribute'));

    //     $model->asEncryptedArrayObjectAttribute = ['foo' => 'baz'];
    //     $this->assertTrue($model->isDirty('asEncryptedArrayObjectAttribute'));
    // }

    public function testDirtyOnEnumCollectionObject()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asEnumCollectionAttribute' => '["draft", "pending"]',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(BaseCollection::class, $model->asEnumCollectionAttribute);
        $this->assertFalse($model->isDirty('asEnumCollectionAttribute'));

        $model->asEnumCollectionAttribute = ['draft', 'pending'];
        $this->assertFalse($model->isDirty('asEnumCollectionAttribute'));

        $model->asEnumCollectionAttribute = ['draft', 'done'];
        $this->assertTrue($model->isDirty('asEnumCollectionAttribute'));
    }

    public function testDirtyOnCustomEnumCollectionObject()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asCustomEnumCollectionAttribute' => '["draft", "pending"]',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(BaseCollection::class, $model->asCustomEnumCollectionAttribute);
        $this->assertFalse($model->isDirty('asCustomEnumCollectionAttribute'));

        $model->asCustomEnumCollectionAttribute = ['draft', 'pending'];
        $this->assertFalse($model->isDirty('asCustomEnumCollectionAttribute'));

        $model->asCustomEnumCollectionAttribute = ['draft', 'done'];
        $this->assertTrue($model->isDirty('asCustomEnumCollectionAttribute'));
    }

    public function testDirtyOnEnumArrayObject()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asEnumArrayObjectAttribute' => '["draft", "pending"]',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(ArrayObject::class, $model->asEnumArrayObjectAttribute);
        $this->assertFalse($model->isDirty('asEnumArrayObjectAttribute'));

        $model->asEnumArrayObjectAttribute = ['draft', 'pending'];
        $this->assertFalse($model->isDirty('asEnumArrayObjectAttribute'));

        $model->asEnumArrayObjectAttribute = ['draft', 'done'];
        $this->assertTrue($model->isDirty('asEnumArrayObjectAttribute'));
    }

    public function testDirtyOnCustomEnumArrayObjectUsing()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'asCustomEnumArrayObjectAttribute' => '["draft", "pending"]',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(ArrayObject::class, $model->asCustomEnumArrayObjectAttribute);
        $this->assertFalse($model->isDirty('asCustomEnumArrayObjectAttribute'));

        $model->asCustomEnumArrayObjectAttribute = ['draft', 'pending'];
        $this->assertFalse($model->isDirty('asCustomEnumArrayObjectAttribute'));

        $model->asCustomEnumArrayObjectAttribute = ['draft', 'done'];
        $this->assertTrue($model->isDirty('asCustomEnumArrayObjectAttribute'));
    }

    public function testHasCastsOnEnumAttribute()
    {
        $model = new EloquentModelEnumCastingStub();
        $this->assertTrue($model->hasCast('enumAttribute', StringStatus::class));
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

    public function testCleanWhenFloatUpdateAttribute()
    {
        // test is equivalent
        $model = new EloquentModelStub(['castedFloat' => 8 - 6.4]);
        $model->syncOriginal();
        $model->castedFloat = 1.6;
        $this->assertTrue($model->originalIsEquivalent('castedFloat'));

        // test is not equivalent
        $model = new EloquentModelStub(['castedFloat' => 5.6]);
        $model->syncOriginal();
        $model->castedFloat = 5.5;
        $this->assertFalse($model->originalIsEquivalent('castedFloat'));
    }

    public function testCalculatedAttributes()
    {
        $model = new EloquentModelStub;
        $model->password = 'secret';
        $attributes = $model->getAttributes();

        // ensure password attribute was not set to null
        $this->assertArrayNotHasKey('password', $attributes);
        $this->assertSame('******', $model->password);

        $hash = 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4';

        $this->assertEquals($hash, $attributes['password_hash']);
        $this->assertEquals($hash, $model->password_hash);
    }

    public function testArrayAccessToAttributes()
    {
        $model = new EloquentModelStub(['attributes' => 1, 'connection' => 2, 'table' => 3]);
        unset($model['table']);

        $this->assertTrue(isset($model['attributes']));
        $this->assertEquals(1, $model['attributes']);
        $this->assertTrue(isset($model['connection']));
        $this->assertEquals(2, $model['connection']);
        $this->assertFalse(isset($model['table']));
        $this->assertEquals(null, $model['table']);
        $this->assertFalse(isset($model['with']));
    }

    public function testOnly()
    {
        $model = new EloquentModelStub;
        $model->first_name = 'taylor';
        $model->last_name = 'otwell';
        $model->project = 'laravel';

        $this->assertEquals(['project' => 'laravel'], $model->only('project'));
        $this->assertEquals(['first_name' => 'taylor', 'last_name' => 'otwell'], $model->only('first_name', 'last_name'));
        $this->assertEquals(['first_name' => 'taylor', 'last_name' => 'otwell'], $model->only(['first_name', 'last_name']));
    }

    public function testNewInstanceReturnsNewInstanceWithAttributesSet()
    {
        $model = new EloquentModelStub;
        $instance = $model->newInstance(['name' => 'taylor']);
        $this->assertInstanceOf(EloquentModelStub::class, $instance);
        $this->assertSame('taylor', $instance->name);
    }

    public function testNewInstanceReturnsNewInstanceWithTableSet()
    {
        $model = new EloquentModelStub;
        $model->setTable('test');
        $newInstance = $model->newInstance();

        $this->assertSame('test', $newInstance->getTable());
    }

    public function testNewInstanceReturnsNewInstanceWithMergedCasts()
    {
        $model = new EloquentModelStub;
        $model->mergeCasts(['foo' => 'date']);
        $newInstance = $model->newInstance();

        $this->assertArrayHasKey('foo', $newInstance->getCasts());
        $this->assertSame('date', $newInstance->getCasts()['foo']);
    }

    public function testCreateMethodSavesNewModel()
    {
        $_SERVER['__eloquent.saved'] = false;
        $model = EloquentModelSaveStub::create(['name' => 'taylor']);
        $this->assertTrue($_SERVER['__eloquent.saved']);
        $this->assertSame('taylor', $model->name);
    }

    public function testMakeMethodDoesNotSaveNewModel()
    {
        $_SERVER['__eloquent.saved'] = false;
        $model = EloquentModelSaveStub::make(['name' => 'taylor']);
        $this->assertFalse($_SERVER['__eloquent.saved']);
        $this->assertSame('taylor', $model->name);
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
        EloquentModelFindWithWritePdoStub::onWriteConnection()->find(1);
    }

    public function testDestroyMethodCallsQueryBuilderCorrectly()
    {
        EloquentModelDestroyStub::destroy(1, 2, 3);
    }

    public function testDestroyMethodCallsQueryBuilderCorrectlyWithCollection()
    {
        EloquentModelDestroyStub::destroy(new BaseCollection([1, 2, 3]));
    }

    public function testDestroyMethodCallsQueryBuilderCorrectlyWithEloquentCollection()
    {
        EloquentModelDestroyStub::destroy(new Collection([
            new EloquentModelDestroyStub(['id' => 1]),
            new EloquentModelDestroyStub(['id' => 2]),
            new EloquentModelDestroyStub(['id' => 3]),
        ]));
    }

    public function testDestroyMethodCallsQueryBuilderCorrectlyWithMultipleArgs()
    {
        EloquentModelDestroyStub::destroy(1, 2, 3);
    }

    public function testDestroyMethodCallsQueryBuilderCorrectlyWithEmptyIds()
    {
        $count = EloquentModelEmptyDestroyStub::destroy([]);
        $this->assertSame(0, $count);
    }

    public function testWithMethodCallsQueryBuilderCorrectly()
    {
        $result = EloquentModelWithStub::with('foo', 'bar');
        $this->assertSame('foo', $result);
    }

    public function testWithoutMethodRemovesEagerLoadedRelationshipCorrectly()
    {
        $model = new EloquentModelWithoutRelationStub;
        $this->addMockConnection($model);
        $instance = $model->newInstance()->newQuery()->without('foo');
        $this->assertEmpty($instance->getEagerLoads());
    }

    public function testWithOnlyMethodLoadsRelationshipCorrectly()
    {
        $model = new EloquentModelWithoutRelationStub();
        $this->addMockConnection($model);
        $instance = $model->newInstance()->newQuery()->withOnly('taylor');
        $this->assertNotNull($instance->getEagerLoads()['taylor']);
        $this->assertArrayNotHasKey('foo', $instance->getEagerLoads());
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

    public function testWithWhereHasWithSpecificColumns()
    {
        $model = new EloquentModelWithWhereHasStub;
        $instance = $model->newInstance()->newQuery()->withWhereHas('foo:diaa,fares');
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('select')->once()->with(['diaa', 'fares']);
        $this->assertNotNull($instance->getEagerLoads()['foo']);
        $closure = $instance->getEagerLoads()['foo'];
        $closure($builder);
    }

    public function testWithWhereHasWorksInNestedQuery()
    {
        $model = new EloquentModelWithWhereHasStub;
        $instance = $model->newInstance()->newQuery()->where(fn (Builder $q) => $q->withWhereHas('foo:diaa,fares'));
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('select')->once()->with(['diaa', 'fares']);
        $this->assertNotNull($instance->getEagerLoads()['foo']);
        $closure = $instance->getEagerLoads()['foo'];
        $closure($builder);
    }

    public function testWithMethodCallsQueryBuilderCorrectlyWithArray()
    {
        $result = EloquentModelWithStub::with(['foo', 'bar']);
        $this->assertSame('foo', $result);
    }

    public function testUpdateProcess()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['name' => 'taylor'])->andReturn(1);
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('updateTimestamps');
        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->once()->with('eloquent.updated: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->once()->with('eloquent.saved: '.get_class($model), $model)->andReturn(true);

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
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['created_at' => 'foo', 'updated_at' => 'bar'])->andReturn(1);
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until');
        $events->shouldReceive('dispatch');

        $model->id = 1;
        $model->syncOriginal();
        $model->created_at = 'foo';
        $model->updated_at = 'bar';
        $model->exists = true;
        $this->assertTrue($model->save());
    }

    public function testSaveIsCanceledIfSavingEventReturnsFalse()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery'])->getMock();
        $query = m::mock(Builder::class);
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(false);
        $model->exists = true;

        $this->assertFalse($model->save());
    }

    public function testUpdateIsCanceledIfUpdatingEventReturnsFalse()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery'])->getMock();
        $query = m::mock(Builder::class);
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(false);
        $model->exists = true;
        $model->foo = 'bar';

        $this->assertFalse($model->save());
    }

    public function testEventsCanBeFiredWithCustomEventObjects()
    {
        $model = $this->getMockBuilder(EloquentModelEventObjectStub::class)->onlyMethods(['newModelQuery'])->getMock();
        $query = m::mock(Builder::class);
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with(m::type(EloquentModelSavingEventStub::class))->andReturn(false);
        $model->exists = true;

        $this->assertFalse($model->save());
    }

    public function testUpdateProcessWithoutTimestamps()
    {
        $model = $this->getMockBuilder(EloquentModelEventObjectStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'fireModelEvent'])->getMock();
        $model->timestamps = false;
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['name' => 'taylor'])->andReturn(1);
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->never())->method('updateTimestamps');
        $model->expects($this->any())->method('fireModelEvent')->willReturn(true);

        $model->id = 1;
        $model->syncOriginal();
        $model->name = 'taylor';
        $model->exists = true;
        $this->assertTrue($model->save());
    }

    public function testUpdateUsesOldPrimaryKey()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', '=', 1);
        $query->shouldReceive('update')->once()->with(['id' => 2, 'foo' => 'bar'])->andReturn(1);
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('updateTimestamps');
        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.updating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->once()->with('eloquent.updated: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->once()->with('eloquent.saved: '.get_class($model), $model)->andReturn(true);

        $model->id = 1;
        $model->syncOriginal();
        $model->id = 2;
        $model->foo = 'bar';
        $model->exists = true;

        $this->assertTrue($model->save());
    }

    public function testTimestampsAreReturnedAsObjects()
    {
        $model = $this->getMockBuilder(EloquentDateModelStub::class)->onlyMethods(['getDateFormat'])->getMock();
        $model->expects($this->any())->method('getDateFormat')->willReturn('Y-m-d');
        $model->setRawAttributes([
            'created_at' => '2012-12-04',
            'updated_at' => '2012-12-05',
        ]);

        $this->assertInstanceOf(Carbon::class, $model->created_at);
        $this->assertInstanceOf(Carbon::class, $model->updated_at);
    }

    public function testTimestampsAreReturnedAsObjectsFromPlainDatesAndTimestamps()
    {
        $model = $this->getMockBuilder(EloquentDateModelStub::class)->onlyMethods(['getDateFormat'])->getMock();
        $model->expects($this->any())->method('getDateFormat')->willReturn('Y-m-d H:i:s');
        $model->setRawAttributes([
            'created_at' => '2012-12-04',
            'updated_at' => $this->currentTime(),
        ]);

        $this->assertInstanceOf(Carbon::class, $model->created_at);
        $this->assertInstanceOf(Carbon::class, $model->updated_at);
    }

    public function testTimestampsAreReturnedAsObjectsOnCreate()
    {
        $timestamps = [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        $model = new EloquentDateModelStub;
        Model::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));
        $resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock(stdClass::class));
        $mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
        $mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $instance = $model->newInstance($timestamps);
        $this->assertInstanceOf(Carbon::class, $instance->updated_at);
        $this->assertInstanceOf(Carbon::class, $instance->created_at);
    }

    public function testDateTimeAttributesReturnNullIfSetToNull()
    {
        $timestamps = [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        $model = new EloquentDateModelStub;
        Model::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));
        $resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock(stdClass::class));
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
        $this->assertInstanceOf(Carbon::class, $model->created_at);

        $model = new EloquentDateModelStub;
        $model->created_at = $this->currentTime();
        $this->assertInstanceOf(Carbon::class, $model->created_at);

        $model = new EloquentDateModelStub;
        $model->created_at = 0;
        $this->assertInstanceOf(Carbon::class, $model->created_at);

        $model = new EloquentDateModelStub;
        $model->created_at = '2012-01-01';
        $this->assertInstanceOf(Carbon::class, $model->created_at);
    }

    public function testFromDateTime()
    {
        $model = new EloquentModelStub;

        $value = Carbon::parse('2015-04-17 22:59:01');
        $this->assertSame('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = new DateTime('2015-04-17 22:59:01');
        $this->assertInstanceOf(DateTime::class, $value);
        $this->assertInstanceOf(DateTimeInterface::class, $value);
        $this->assertSame('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = new DateTimeImmutable('2015-04-17 22:59:01');
        $this->assertInstanceOf(DateTimeImmutable::class, $value);
        $this->assertInstanceOf(DateTimeInterface::class, $value);
        $this->assertSame('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = '2015-04-17 22:59:01';
        $this->assertSame('2015-04-17 22:59:01', $model->fromDateTime($value));

        $value = '2015-04-17';
        $this->assertSame('2015-04-17 00:00:00', $model->fromDateTime($value));

        $value = '2015-4-17';
        $this->assertSame('2015-04-17 00:00:00', $model->fromDateTime($value));

        $value = '1429311541';
        $this->assertSame('2015-04-17 22:59:01', $model->fromDateTime($value));

        $this->assertNull($model->fromDateTime(null));
    }

    public function testFromDateTimeMilliseconds()
    {
        $model = $this->getMockBuilder('Illuminate\Tests\Database\EloquentDateModelStub')->onlyMethods(['getDateFormat'])->getMock();
        $model->expects($this->any())->method('getDateFormat')->willReturn('Y-m-d H:s.vi');
        $model->setRawAttributes([
            'created_at' => '2012-12-04 22:59.32130',
        ]);

        $this->assertInstanceOf(Carbon::class, $model->created_at);
        $this->assertSame('22:30:59.321000', $model->created_at->format('H:i:s.u'));
    }

    public function testInsertProcess()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('updateTimestamps');

        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->once()->with('eloquent.created: '.get_class($model), $model);
        $events->shouldReceive('dispatch')->once()->with('eloquent.saved: '.get_class($model), $model);

        $model->name = 'taylor';
        $model->exists = false;
        $this->assertTrue($model->save());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);

        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insert')->once()->with(['name' => 'taylor']);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('updateTimestamps');
        $model->setIncrementing(false);

        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->once()->with('eloquent.created: '.get_class($model), $model);
        $events->shouldReceive('dispatch')->once()->with('eloquent.saved: '.get_class($model), $model);

        $model->name = 'taylor';
        $model->exists = false;
        $this->assertTrue($model->save());
        $this->assertNull($model->id);
        $this->assertTrue($model->exists);
    }

    public function testInsertIsCanceledIfCreatingEventReturnsFalse()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->once()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->once()->with('eloquent.creating: '.get_class($model), $model)->andReturn(false);

        $this->assertFalse($model->save());
        $this->assertFalse($model->exists);
    }

    public function testDeleteProperlyDeletesModel()
    {
        $model = $this->getMockBuilder(Model::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'touchOwners'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', '=', 1)->andReturn($query);
        $query->shouldReceive('delete')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('touchOwners');
        $model->exists = true;
        $model->id = 1;
        $model->delete();
    }

    public function testPushNoRelations()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
    }

    public function testPushEmptyOneRelation()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
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
        $related1 = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'related1'], 'id')->andReturn(2);
        $query->shouldReceive('getConnection')->once();
        $related1->expects($this->once())->method('newModelQuery')->willReturn($query);
        $related1->expects($this->once())->method('updateTimestamps');
        $related1->name = 'related1';
        $related1->exists = false;

        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
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
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;
        $model->setRelation('relationMany', new Collection([]));

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
        $this->assertCount(0, $model->relationMany);
    }

    public function testPushManyRelation()
    {
        $related1 = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'related1'], 'id')->andReturn(2);
        $query->shouldReceive('getConnection')->once();
        $related1->expects($this->once())->method('newModelQuery')->willReturn($query);
        $related1->expects($this->once())->method('updateTimestamps');
        $related1->name = 'related1';
        $related1->exists = false;

        $related2 = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'related2'], 'id')->andReturn(3);
        $query->shouldReceive('getConnection')->once();
        $related2->expects($this->once())->method('newModelQuery')->willReturn($query);
        $related2->expects($this->once())->method('updateTimestamps');
        $related2->name = 'related2';
        $related2->exists = false;

        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with(['name' => 'taylor'], 'id')->andReturn(1);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);
        $model->expects($this->once())->method('updateTimestamps');

        $model->name = 'taylor';
        $model->exists = false;
        $model->setRelation('relationMany', new Collection([$related1, $related2]));

        $this->assertTrue($model->push());
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);
        $this->assertCount(2, $model->relationMany);
        $this->assertEquals([2, 3], $model->relationMany->pluck('id')->all());
    }

    public function testPushCircularRelations()
    {
        $parent = new EloquentModelWithRecursiveRelationshipsStub(['id' => 1, 'parent_id' => null]);
        $lastId = $parent->id;
        $parent->setRelation('self', $parent);

        $children = new Collection();
        for ($count = 0; $count < 2; $count++) {
            $child = new EloquentModelWithRecursiveRelationshipsStub(['id' => ++$lastId, 'parent_id' => $parent->id]);
            $child->setRelation('parent', $parent);
            $child->setRelation('self', $child);
            $children->push($child);
        }
        $parent->setRelation('children', $children);

        try {
            $this->assertTrue($parent->push());
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testNewQueryReturnsEloquentQueryBuilder()
    {
        $conn = m::mock(Connection::class);
        $grammar = m::mock(Grammar::class);
        $processor = m::mock(Processor::class);
        EloquentModelStub::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));
        $conn->shouldReceive('query')->andReturnUsing(function () use ($conn, $grammar, $processor) {
            return new BaseBuilder($conn, $grammar, $processor);
        });
        $resolver->shouldReceive('connection')->andReturn($conn);
        $model = new EloquentModelStub;
        $builder = $model->newQuery();
        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function testGetAndSetTableOperations()
    {
        $model = new EloquentModelStub;
        $this->assertSame('stub', $model->getTable());
        $model->setTable('foo');
        $this->assertSame('foo', $model->getTable());
    }

    public function testGetKeyReturnsValueOfPrimaryKey()
    {
        $model = new EloquentModelStub;
        $model->id = 1;
        $this->assertEquals(1, $model->getKey());
        $this->assertSame('id', $model->getKeyName());
    }

    public function testConnectionManagement()
    {
        EloquentModelStub::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));
        $model = m::mock(EloquentModelStub::class.'[getConnectionName,connection]');

        $retval = $model->setConnection('foo');
        $this->assertEquals($retval, $model);
        $this->assertSame('foo', $model->connection);

        $model->shouldReceive('getConnectionName')->once()->andReturn('somethingElse');
        $resolver->shouldReceive('connection')->once()->with('somethingElse')->andReturn('bar');

        $this->assertSame('bar', $model->getConnection());
    }

    public function testToArray()
    {
        $model = new EloquentModelStub;
        $model->name = 'foo';
        $model->age = null;
        $model->password = 'password1';
        $model->setHidden(['password']);
        $model->setRelation('names', new BaseCollection([
            new EloquentModelStub(['bar' => 'baz']), new EloquentModelStub(['bam' => 'boom']),
        ]));
        $model->setRelation('partner', new EloquentModelStub(['name' => 'abby']));
        $model->setRelation('group', null);
        $model->setRelation('multi', new BaseCollection);
        $array = $model->toArray();

        $this->assertIsArray($array);
        $this->assertSame('foo', $array['name']);
        $this->assertSame('baz', $array['names'][0]['bar']);
        $this->assertSame('boom', $array['names'][1]['bam']);
        $this->assertSame('abby', $array['partner']['name']);
        $this->assertNull($array['group']);
        $this->assertEquals([], $array['multi']);
        $this->assertFalse(isset($array['password']));

        $model->setAppends(['appendable']);
        $array = $model->toArray();
        $this->assertSame('appended', $array['appendable']);
    }

    public function testToArrayWithCircularRelations()
    {
        $parent = new EloquentModelWithRecursiveRelationshipsStub(['id' => 1, 'parent_id' => null]);
        $lastId = $parent->id;
        $parent->setRelation('self', $parent);

        $children = new Collection();
        for ($count = 0; $count < 2; $count++) {
            $child = new EloquentModelWithRecursiveRelationshipsStub(['id' => ++$lastId, 'parent_id' => $parent->id]);
            $child->setRelation('parent', $parent);
            $child->setRelation('self', $child);
            $children->push($child);
        }
        $parent->setRelation('children', $children);

        try {
            $this->assertSame(
                [
                    'id' => 1,
                    'parent_id' => null,
                    'self' => ['id' => 1, 'parent_id' => null],
                    'children' => [
                        [
                            'id' => 2,
                            'parent_id' => 1,
                            'parent' => ['id' => 1, 'parent_id' => null],
                            'self' => ['id' => 2, 'parent_id' => 1],
                        ],
                        [
                            'id' => 3,
                            'parent_id' => 1,
                            'parent' => ['id' => 1, 'parent_id' => null],
                            'self' => ['id' => 3, 'parent_id' => 1],
                        ],
                    ],
                ],
                $parent->toArray()
            );
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testGetQueueableRelationsWithCircularRelations()
    {
        $parent = new EloquentModelWithRecursiveRelationshipsStub(['id' => 1, 'parent_id' => null]);
        $lastId = $parent->id;
        $parent->setRelation('self', $parent);

        $children = new Collection();
        for ($count = 0; $count < 2; $count++) {
            $child = new EloquentModelWithRecursiveRelationshipsStub(['id' => ++$lastId, 'parent_id' => $parent->id]);
            $child->setRelation('parent', $parent);
            $child->setRelation('self', $child);
            $children->push($child);
        }
        $parent->setRelation('children', $children);

        try {
            $this->assertSame(
                [
                    'self',
                    'children',
                    'children.parent',
                    'children.self',
                ],
                $parent->getQueueableRelations()
            );
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage());
        }
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

        $model->setRelation('foo', ['bar']);
        $model->setRelation('bam', ['boom']);
        $model->setHidden(['foo']);

        $array = $method->invokeArgs($model, []);

        $this->assertSame(['bam' => ['boom']], $array);
    }

    public function testToArraySnakeAttributes()
    {
        $model = new EloquentModelStub;
        $model->setRelation('namesList', new BaseCollection([
            new EloquentModelStub(['bar' => 'baz']), new EloquentModelStub(['bam' => 'boom']),
        ]));
        $array = $model->toArray();

        $this->assertSame('baz', $array['names_list'][0]['bar']);
        $this->assertSame('boom', $array['names_list'][1]['bam']);

        $model = new EloquentModelCamelStub;
        $model->setRelation('namesList', new BaseCollection([
            new EloquentModelStub(['bar' => 'baz']), new EloquentModelStub(['bam' => 'boom']),
        ]));
        $array = $model->toArray();

        $this->assertSame('baz', $array['namesList'][0]['bar']);
        $this->assertSame('boom', $array['namesList'][1]['bam']);
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

    public function testMakeVisibleIf()
    {
        $model = new EloquentModelStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $model->setHidden(['age', 'id']);
        $model->makeVisibleIf(true, 'age');
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayNotHasKey('id', $array);

        $model->setHidden(['age', 'id']);
        $model->makeVisibleIf(false, 'age');
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
        $this->assertArrayNotHasKey('id', $array);

        $model->setHidden(['age', 'id']);
        $model->makeVisibleIf(function ($model) {
            return ! is_null($model->name);
        }, 'age');
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayNotHasKey('id', $array);
    }

    public function testMakeHiddenIf()
    {
        $model = new EloquentModelStub(['name' => 'foo', 'age' => 'bar', 'address' => 'foobar', 'id' => 'baz']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayHasKey('address', $array);
        $this->assertArrayHasKey('id', $array);

        $array = $model->makeHiddenIf(true, 'address')->toArray();
        $this->assertArrayNotHasKey('address', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayHasKey('id', $array);

        $model->makeVisible('address');

        $array = $model->makeHiddenIf(false, ['name', 'age'])->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayHasKey('address', $array);
        $this->assertArrayHasKey('id', $array);

        $array = $model->makeHiddenIf(function ($model) {
            return ! is_null($model->id);
        }, ['name', 'age'])->toArray();
        $this->assertArrayHasKey('address', $array);
        $this->assertArrayNotHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
        $this->assertArrayHasKey('id', $array);
    }

    public function testFillable()
    {
        $model = new EloquentModelStub;
        $model->fillable(['name', 'age']);
        $model->fill(['name' => 'foo', 'age' => 'bar']);
        $this->assertSame('foo', $model->name);
        $this->assertSame('bar', $model->age);
    }

    public function testQualifyColumn()
    {
        $model = new EloquentModelStub;

        $this->assertSame('stub.column', $model->qualifyColumn('column'));
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
        $this->assertSame('foo', $model->name);
        $this->assertSame('bar', $model->age);
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

        EloquentModelStub::setConnectionResolver($resolver = m::mock(Resolver::class));
        $resolver->shouldReceive('connection')->andReturn($connection = m::mock(stdClass::class));
        $connection->shouldReceive('getSchemaBuilder->getColumnListing')->andReturn(['name', 'age', 'foo']);

        $model->guard(['name', 'age']);
        $model->fill(['name' => 'foo', 'age' => 'bar', 'foo' => 'bar']);
        $this->assertFalse(isset($model->name));
        $this->assertFalse(isset($model->age));
        $this->assertSame('bar', $model->foo);

        $model = new EloquentModelStub;
        $model->guard(['name', 'age']);
        $model->fill(['Foo' => 'bar']);
        $this->assertFalse(isset($model->Foo));

        $handledMassAssignmentExceptions = 0;

        Model::preventSilentlyDiscardingAttributes();

        $this->expectException(MassAssignmentException::class);
        $model = new EloquentModelStub;
        $model->guard(['name', 'age']);
        $model->fill(['Foo' => 'bar']);

        Model::preventSilentlyDiscardingAttributes(false);
    }

    public function testUsesOverriddenHandlerWhenDiscardingAttributes()
    {
        EloquentModelStub::setConnectionResolver($resolver = m::mock(Resolver::class));
        $resolver->shouldReceive('connection')->andReturn($connection = m::mock(stdClass::class));
        $connection->shouldReceive('getSchemaBuilder->getColumnListing')->andReturn(['name', 'age', 'foo']);

        Model::preventSilentlyDiscardingAttributes();

        $callbackModel = null;
        $callbackKeys = null;
        Model::handleDiscardedAttributeViolationUsing(function ($model, $keys) use (&$callbackModel, &$callbackKeys) {
            $callbackModel = $model;
            $callbackKeys = $keys;
        });

        $model = new EloquentModelStub;
        $model->guard(['name', 'age']);
        $model->fill(['Foo' => 'bar']);

        $this->assertInstanceOf(EloquentModelStub::class, $callbackModel);
        $this->assertEquals(['Foo'], $callbackKeys);

        Model::preventSilentlyDiscardingAttributes(false);
        Model::handleDiscardedAttributeViolationUsing(null);
    }

    public function testFillableOverridesGuarded()
    {
        Model::preventSilentlyDiscardingAttributes(false);

        $model = new EloquentModelStub;
        $model->guard([]);
        $model->fillable(['age', 'foo']);
        $model->fill(['name' => 'foo', 'age' => 'bar', 'foo' => 'bar']);
        $this->assertFalse(isset($model->name));
        $this->assertSame('bar', $model->age);
        $this->assertSame('bar', $model->foo);
    }

    public function testGlobalGuarded()
    {
        $this->expectException(MassAssignmentException::class);
        $this->expectExceptionMessage('name');

        $model = new EloquentModelStub;
        $model->guard(['*']);
        $model->fill(['name' => 'foo', 'age' => 'bar', 'votes' => 'baz']);
    }

    public function testUnguardedRunsCallbackWhileBeingUnguarded()
    {
        $model = Model::unguarded(function () {
            return (new EloquentModelStub)->guard(['*'])->fill(['name' => 'Taylor']);
        });
        $this->assertSame('Taylor', $model->name);
        $this->assertFalse(Model::isUnguarded());
    }

    public function testUnguardedCallDoesNotChangeUnguardedState()
    {
        Model::unguard();
        $model = Model::unguarded(function () {
            return (new EloquentModelStub)->guard(['*'])->fill(['name' => 'Taylor']);
        });
        $this->assertSame('Taylor', $model->name);
        $this->assertTrue(Model::isUnguarded());
        Model::reguard();
    }

    public function testUnguardedCallDoesNotChangeUnguardedStateOnException()
    {
        try {
            Model::unguarded(function () {
                throw new Exception;
            });
        } catch (Exception) {
            // ignore the exception
        }
        $this->assertFalse(Model::isUnguarded());
    }

    public function testHasOneCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasOne(EloquentModelSaveStub::class);
        $this->assertSame('save_stub.eloquent_model_stub_id', $relation->getQualifiedForeignKeyName());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasOne(EloquentModelSaveStub::class, 'foo');
        $this->assertSame('save_stub.foo', $relation->getQualifiedForeignKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf(EloquentModelSaveStub::class, $relation->getQuery()->getModel());
    }

    public function testMorphOneCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->morphOne(EloquentModelSaveStub::class, 'morph');
        $this->assertSame('save_stub.morph_id', $relation->getQualifiedForeignKeyName());
        $this->assertSame('save_stub.morph_type', $relation->getQualifiedMorphType());
        $this->assertEquals(EloquentModelStub::class, $relation->getMorphClass());
    }

    public function testCorrectMorphClassIsReturned()
    {
        Relation::morphMap(['alias' => 'AnotherModel']);
        $model = new EloquentModelStub;

        try {
            $this->assertEquals(EloquentModelStub::class, $model->getMorphClass());
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function testHasManyCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasMany(EloquentModelSaveStub::class);
        $this->assertSame('save_stub.eloquent_model_stub_id', $relation->getQualifiedForeignKeyName());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->hasMany(EloquentModelSaveStub::class, 'foo');

        $this->assertSame('save_stub.foo', $relation->getQualifiedForeignKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf(EloquentModelSaveStub::class, $relation->getQuery()->getModel());
    }

    public function testMorphManyCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->morphMany(EloquentModelSaveStub::class, 'morph');
        $this->assertSame('save_stub.morph_id', $relation->getQualifiedForeignKeyName());
        $this->assertSame('save_stub.morph_type', $relation->getQualifiedMorphType());
        $this->assertEquals(EloquentModelStub::class, $relation->getMorphClass());
    }

    public function testBelongsToCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->belongsToStub();
        $this->assertSame('belongs_to_stub_id', $relation->getForeignKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf(EloquentModelSaveStub::class, $relation->getQuery()->getModel());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->belongsToExplicitKeyStub();
        $this->assertSame('foo', $relation->getForeignKeyName());
    }

    public function testMorphToCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        // $this->morphTo();
        $model->setAttribute('morph_to_stub_type', EloquentModelSaveStub::class);
        $relation = $model->morphToStub();
        $this->assertSame('morph_to_stub_id', $relation->getForeignKeyName());
        $this->assertSame('morph_to_stub_type', $relation->getMorphType());
        $this->assertSame('morphToStub', $relation->getRelationName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf(EloquentModelSaveStub::class, $relation->getQuery()->getModel());

        // $this->morphTo(null, 'type', 'id');
        $relation2 = $model->morphToStubWithKeys();
        $this->assertSame('id', $relation2->getForeignKeyName());
        $this->assertSame('type', $relation2->getMorphType());
        $this->assertSame('morphToStubWithKeys', $relation2->getRelationName());

        // $this->morphTo('someName');
        $relation3 = $model->morphToStubWithName();
        $this->assertSame('some_name_id', $relation3->getForeignKeyName());
        $this->assertSame('some_name_type', $relation3->getMorphType());
        $this->assertSame('someName', $relation3->getRelationName());

        // $this->morphTo('someName', 'type', 'id');
        $relation4 = $model->morphToStubWithNameAndKeys();
        $this->assertSame('id', $relation4->getForeignKeyName());
        $this->assertSame('type', $relation4->getMorphType());
        $this->assertSame('someName', $relation4->getRelationName());
    }

    public function testBelongsToManyCreatesProperRelation()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        $relation = $model->belongsToMany(EloquentModelSaveStub::class);
        $this->assertSame('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_stub_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertSame('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_save_stub_id', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf(EloquentModelSaveStub::class, $relation->getQuery()->getModel());
        $this->assertEquals(__FUNCTION__, $relation->getRelationName());

        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $relation = $model->belongsToMany(EloquentModelSaveStub::class, 'table', 'foreign', 'other');
        $this->assertSame('table.foreign', $relation->getQualifiedForeignPivotKeyName());
        $this->assertSame('table.other', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertSame($model, $relation->getParent());
        $this->assertInstanceOf(EloquentModelSaveStub::class, $relation->getQuery()->getModel());
    }

    public function testRelationsWithVariedConnections()
    {
        // Has one
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasOne(EloquentNoConnectionModelStub::class);
        $this->assertSame('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasOne(EloquentDifferentConnectionModelStub::class);
        $this->assertSame('different_connection', $relation->getRelated()->getConnectionName());

        // Morph One
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->morphOne(EloquentNoConnectionModelStub::class, 'type');
        $this->assertSame('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->morphOne(EloquentDifferentConnectionModelStub::class, 'type');
        $this->assertSame('different_connection', $relation->getRelated()->getConnectionName());

        // Belongs to
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsTo(EloquentNoConnectionModelStub::class);
        $this->assertSame('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsTo(EloquentDifferentConnectionModelStub::class);
        $this->assertSame('different_connection', $relation->getRelated()->getConnectionName());

        // has many
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasMany(EloquentNoConnectionModelStub::class);
        $this->assertSame('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasMany(EloquentDifferentConnectionModelStub::class);
        $this->assertSame('different_connection', $relation->getRelated()->getConnectionName());

        // has many through
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasManyThrough(EloquentNoConnectionModelStub::class, EloquentModelSaveStub::class);
        $this->assertSame('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->hasManyThrough(EloquentDifferentConnectionModelStub::class, EloquentModelSaveStub::class);
        $this->assertSame('different_connection', $relation->getRelated()->getConnectionName());

        // belongs to many
        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsToMany(EloquentNoConnectionModelStub::class);
        $this->assertSame('non_default', $relation->getRelated()->getConnectionName());

        $model = new EloquentModelStub;
        $model->setConnection('non_default');
        $this->addMockConnection($model);
        $relation = $model->belongsToMany(EloquentDifferentConnectionModelStub::class);
        $this->assertSame('different_connection', $relation->getRelated()->getConnectionName());
    }

    public function testModelsAssumeTheirName()
    {
        require_once __DIR__.'/stubs/EloquentModelNamespacedStub.php';

        $model = new EloquentModelWithoutTableStub;
        $this->assertSame('eloquent_model_without_table_stubs', $model->getTable());

        $namespacedModel = new EloquentModelNamespacedStub;
        $this->assertSame('eloquent_model_namespaced_stubs', $namespacedModel->getTable());
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
        $this->assertSame('foo', $model->getRouteKey());
    }

    public function testRouteNameIsPrimaryKeyName()
    {
        $model = new EloquentModelStub;
        $this->assertSame('id', $model->getRouteKeyName());
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
        $this->assertSame('taylor', $clone->first);
        $this->assertSame('otwell', $clone->last);
        $this->assertArrayNotHasKey('created_at', $clone->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $clone->getAttributes());
        $this->assertEquals(['bar'], $clone->foo);
    }

    public function testCloneModelMakesAFreshCopyOfTheModelWhenModelHasUuidPrimaryKey()
    {
        $class = new EloquentPrimaryUuidModelStub();
        $class->uuid = 'ccf55569-bc4a-4450-875f-b5cffb1b34ec';
        $class->exists = true;
        $class->first = 'taylor';
        $class->last = 'otwell';
        $class->created_at = $class->freshTimestamp();
        $class->updated_at = $class->freshTimestamp();
        $class->setRelation('foo', ['bar']);

        $clone = $class->replicate();

        $this->assertNull($clone->uuid);
        $this->assertFalse($clone->exists);
        $this->assertSame('taylor', $clone->first);
        $this->assertSame('otwell', $clone->last);
        $this->assertArrayNotHasKey('created_at', $clone->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $clone->getAttributes());
        $this->assertEquals(['bar'], $clone->foo);
    }

    public function testCloneModelMakesAFreshCopyOfTheModelWhenModelHasUuid()
    {
        $class = new EloquentNonPrimaryUuidModelStub();
        $class->id = 1;
        $class->uuid = 'ccf55569-bc4a-4450-875f-b5cffb1b34ec';
        $class->exists = true;
        $class->first = 'taylor';
        $class->last = 'otwell';
        $class->created_at = $class->freshTimestamp();
        $class->updated_at = $class->freshTimestamp();
        $class->setRelation('foo', ['bar']);

        $clone = $class->replicate();

        $this->assertNull($clone->id);
        $this->assertNull($clone->uuid);
        $this->assertFalse($clone->exists);
        $this->assertSame('taylor', $clone->first);
        $this->assertSame('otwell', $clone->last);
        $this->assertArrayNotHasKey('created_at', $clone->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $clone->getAttributes());
        $this->assertEquals(['bar'], $clone->foo);
    }

    public function testCloneModelMakesAFreshCopyOfTheModelWhenModelHasUlidPrimaryKey()
    {
        $class = new EloquentPrimaryUlidModelStub();
        $class->ulid = '01HBZ975D8606P6CV672KW1AP2';
        $class->exists = true;
        $class->first = 'taylor';
        $class->last = 'otwell';
        $class->created_at = $class->freshTimestamp();
        $class->updated_at = $class->freshTimestamp();
        $class->setRelation('foo', ['bar']);

        $clone = $class->replicate();

        $this->assertNull($clone->ulid);
        $this->assertFalse($clone->exists);
        $this->assertSame('taylor', $clone->first);
        $this->assertSame('otwell', $clone->last);
        $this->assertArrayNotHasKey('created_at', $clone->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $clone->getAttributes());
        $this->assertEquals(['bar'], $clone->foo);
    }

    public function testCloneModelMakesAFreshCopyOfTheModelWhenModelHasUlid()
    {
        $class = new EloquentNonPrimaryUlidModelStub();
        $class->id = 1;
        $class->ulid = '01HBZ975D8606P6CV672KW1AP2';
        $class->exists = true;
        $class->first = 'taylor';
        $class->last = 'otwell';
        $class->created_at = $class->freshTimestamp();
        $class->updated_at = $class->freshTimestamp();
        $class->setRelation('foo', ['bar']);

        $clone = $class->replicate();

        $this->assertNull($clone->id);
        $this->assertNull($clone->ulid);
        $this->assertFalse($clone->exists);
        $this->assertSame('taylor', $clone->first);
        $this->assertSame('otwell', $clone->last);
        $this->assertArrayNotHasKey('created_at', $clone->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $clone->getAttributes());
        $this->assertEquals(['bar'], $clone->foo);
    }

    public function testModelObserversCanBeAttachedToModels()
    {
        EloquentModelStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@saved');
        $events->shouldReceive('forget');
        EloquentModelStub::observe(new EloquentTestObserverStub);
        EloquentModelStub::flushEventListeners();
    }

    public function testModelObserversCanBeAttachedToModelsWithString()
    {
        EloquentModelStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@saved');
        $events->shouldReceive('forget');
        EloquentModelStub::observe(EloquentTestObserverStub::class);
        EloquentModelStub::flushEventListeners();
    }

    public function testModelObserversCanBeAttachedToModelsThroughAnArray()
    {
        EloquentModelStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@saved');
        $events->shouldReceive('forget');
        EloquentModelStub::observe([EloquentTestObserverStub::class]);
        EloquentModelStub::flushEventListeners();
    }

    public function testModelObserversCanBeAttachedToModelsWithStringUsingAttribute()
    {
        EloquentModelWithObserveAttributeStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch');
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelWithObserveAttributeStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelWithObserveAttributeStub', EloquentTestObserverStub::class.'@saved');
        $events->shouldReceive('forget');
        EloquentModelWithObserveAttributeStub::flushEventListeners();
    }

    public function testModelObserversCanBeAttachedToModelsThroughAnArrayUsingAttribute()
    {
        EloquentModelWithObserveAttributeUsingArrayStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch');
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelWithObserveAttributeUsingArrayStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelWithObserveAttributeUsingArrayStub', EloquentTestObserverStub::class.'@saved');
        $events->shouldReceive('forget');
        EloquentModelWithObserveAttributeUsingArrayStub::flushEventListeners();
    }

    public function testModelObserversCanBeAttachedToModelsThroughAttributesOnParentClasses()
    {
        EloquentModelWithObserveAttributeGrandchildStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch');
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelWithObserveAttributeGrandchildStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelWithObserveAttributeGrandchildStub', EloquentTestObserverStub::class.'@saved');
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelWithObserveAttributeGrandchildStub', EloquentTestAnotherObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelWithObserveAttributeGrandchildStub', EloquentTestAnotherObserverStub::class.'@saved');
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelWithObserveAttributeGrandchildStub', EloquentTestThirdObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelWithObserveAttributeGrandchildStub', EloquentTestThirdObserverStub::class.'@saved');
        $events->shouldReceive('forget');
        EloquentModelWithObserveAttributeGrandchildStub::flushEventListeners();
    }

    public function testThrowExceptionOnAttachingNotExistsModelObserverWithString()
    {
        $this->expectException(InvalidArgumentException::class);
        EloquentModelStub::observe(NotExistClass::class);
    }

    public function testThrowExceptionOnAttachingNotExistsModelObserversThroughAnArray()
    {
        $this->expectException(InvalidArgumentException::class);
        EloquentModelStub::observe([NotExistClass::class]);
    }

    public function testModelObserversCanBeAttachedToModelsThroughCallingObserveMethodOnlyOnce()
    {
        EloquentModelStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelStub', EloquentTestObserverStub::class.'@saved');

        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelStub', EloquentTestAnotherObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelStub', EloquentTestAnotherObserverStub::class.'@saved');

        $events->shouldReceive('forget');

        EloquentModelStub::observe([
            EloquentTestObserverStub::class,
            EloquentTestAnotherObserverStub::class,
        ]);

        EloquentModelStub::flushEventListeners();
    }

    public function testWithoutEventDispatcher()
    {
        EloquentModelSaveStub::setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('listen')->once()->with('eloquent.creating: Illuminate\Tests\Database\EloquentModelSaveStub', EloquentTestObserverStub::class.'@creating');
        $events->shouldReceive('listen')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelSaveStub', EloquentTestObserverStub::class.'@saved');
        $events->shouldNotReceive('until');
        $events->shouldNotReceive('dispatch');
        $events->shouldReceive('forget');
        EloquentModelSaveStub::observe(EloquentTestObserverStub::class);

        $model = EloquentModelSaveStub::withoutEvents(function () {
            $model = new EloquentModelSaveStub;
            $model->save();

            return $model;
        });

        $model->withoutEvents(function () use ($model) {
            $model->first_name = 'Taylor';
            $model->save();
        });

        $events->shouldReceive('until')->once()->with('eloquent.saving: Illuminate\Tests\Database\EloquentModelSaveStub', $model);
        $events->shouldReceive('dispatch')->once()->with('eloquent.saved: Illuminate\Tests\Database\EloquentModelSaveStub', $model);

        $model->last_name = 'Otwell';
        $model->save();

        EloquentModelSaveStub::flushEventListeners();
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

    public function testGetModelAttributeMethodThrowsExceptionIfNotRelation()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Illuminate\Tests\Database\EloquentModelStub::incorrectRelationStub must return a relationship instance.');

        $model = new EloquentModelStub;
        $model->incorrectRelationStub;
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
        unserialize($string);
        $this->assertTrue(EloquentModelBootingTestStub::isBooted());
    }

    public function testModelsTraitIsInitialized()
    {
        $model = new EloquentModelStubWithTrait;
        $this->assertTrue($model->fooBarIsInitialized);
    }

    public function testAppendingOfAttributes()
    {
        $model = new EloquentModelAppendsStub;

        $this->assertTrue(isset($model->is_admin));
        $this->assertTrue(isset($model->camelCased));
        $this->assertTrue(isset($model->StudlyCased));

        $this->assertSame('admin', $model->is_admin);
        $this->assertSame('camelCased', $model->camelCased);
        $this->assertSame('StudlyCased', $model->StudlyCased);

        $this->assertEquals(['is_admin', 'camelCased', 'StudlyCased'], $model->getAppends());

        $this->assertTrue($model->hasAppended('is_admin'));
        $this->assertTrue($model->hasAppended('camelCased'));
        $this->assertTrue($model->hasAppended('StudlyCased'));
        $this->assertFalse($model->hasAppended('not_appended'));

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
        $this->assertSame('bar', $replicated->foo);
        $this->assertNull($replicated->created_at);
        $this->assertNull($replicated->updated_at);
    }

    public function testReplicatingEventIsFiredWhenReplicatingModel()
    {
        $model = new EloquentModelStub;

        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with('eloquent.replicating: '.get_class($model), m::on(function ($m) use ($model) {
            return $model->is($m);
        }));

        $model->replicate();
    }

    public function testReplicateQuietlyCreatesANewModelInstanceWithSameAttributeValuesAndIsQuiet()
    {
        $model = new EloquentModelStub;
        $model->id = 'id';
        $model->foo = 'bar';
        $model->created_at = new DateTime;
        $model->updated_at = new DateTime;
        $replicated = $model->replicateQuietly();

        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->never()->with('eloquent.replicating: '.get_class($model), $model)->andReturn(true);

        $this->assertNull($replicated->id);
        $this->assertSame('bar', $replicated->foo);
        $this->assertNull($replicated->created_at);
        $this->assertNull($replicated->updated_at);
    }

    public function testIncrementOnExistingModelCallsQueryAndSetsAttribute()
    {
        $model = m::mock(EloquentModelStub::class.'[newQueryWithoutScopes]');
        $model->exists = true;
        $model->id = 1;
        $model->syncOriginalAttribute('id');
        $model->foo = 2;

        $model->shouldReceive('newQueryWithoutScopes')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->andReturn($query);
        $query->shouldReceive('increment');

        // hmm
        $model->publicIncrement('foo', 1);
        $this->assertFalse($model->isDirty());

        $model->publicIncrement('foo', 1, ['category' => 1]);
        $this->assertEquals(4, $model->foo);
        $this->assertEquals(1, $model->category);
        $this->assertTrue($model->isDirty('category'));
    }

    public function testIncrementQuietlyOnExistingModelCallsQueryAndSetsAttributeAndIsQuiet()
    {
        $model = m::mock(EloquentModelStub::class.'[newQueryWithoutScopes]');
        $model->exists = true;
        $model->id = 1;
        $model->syncOriginalAttribute('id');
        $model->foo = 2;

        $model->shouldReceive('newQueryWithoutScopes')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->andReturn($query);
        $query->shouldReceive('increment');

        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->never()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->never()->with('eloquent.updating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->never()->with('eloquent.updated: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->never()->with('eloquent.saved: '.get_class($model), $model)->andReturn(true);

        $model->publicIncrementQuietly('foo', 1);
        $this->assertFalse($model->isDirty());

        $model->publicIncrementQuietly('foo', 1, ['category' => 1]);
        $this->assertEquals(4, $model->foo);
        $this->assertEquals(1, $model->category);
        $this->assertTrue($model->isDirty('category'));
    }

    public function testDecrementQuietlyOnExistingModelCallsQueryAndSetsAttributeAndIsQuiet()
    {
        $model = m::mock(EloquentModelStub::class.'[newQueryWithoutScopes]');
        $model->exists = true;
        $model->id = 1;
        $model->syncOriginalAttribute('id');
        $model->foo = 4;

        $model->shouldReceive('newQueryWithoutScopes')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->andReturn($query);
        $query->shouldReceive('decrement');

        $model->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('until')->never()->with('eloquent.saving: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('until')->never()->with('eloquent.updating: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->never()->with('eloquent.updated: '.get_class($model), $model)->andReturn(true);
        $events->shouldReceive('dispatch')->never()->with('eloquent.saved: '.get_class($model), $model)->andReturn(true);

        $model->publicDecrementQuietly('foo', 1);
        $this->assertFalse($model->isDirty());

        $model->publicDecrementQuietly('foo', 1, ['category' => 1]);
        $this->assertEquals(2, $model->foo);
        $this->assertEquals(1, $model->category);
        $this->assertTrue($model->isDirty('category'));
    }

    public function testRelationshipTouchOwnersIsPropagated()
    {
        $relation = $this->getMockBuilder(BelongsTo::class)->onlyMethods(['touch'])->disableOriginalConstructor()->getMock();
        $relation->expects($this->once())->method('touch');

        $model = m::mock(EloquentModelStub::class.'[partner]');
        $this->addMockConnection($model);
        $model->shouldReceive('partner')->once()->andReturn($relation);
        $model->setTouchedRelations(['partner']);

        $mockPartnerModel = m::mock(EloquentModelStub::class.'[touchOwners]');
        $mockPartnerModel->shouldReceive('touchOwners')->once();
        $model->setRelation('partner', $mockPartnerModel);

        $model->touchOwners();
    }

    public function testRelationshipTouchOwnersIsNotPropagatedIfNoRelationshipResult()
    {
        $relation = $this->getMockBuilder(BelongsTo::class)->onlyMethods(['touch'])->disableOriginalConstructor()->getMock();
        $relation->expects($this->once())->method('touch');

        $model = m::mock(EloquentModelStub::class.'[partner]');
        $this->addMockConnection($model);
        $model->shouldReceive('partner')->once()->andReturn($relation);
        $model->setTouchedRelations(['partner']);

        $model->setRelation('partner', null);

        $model->touchOwners();
    }

    public function testModelAttributesAreCastedWhenPresentInCastsPropertyOrCastsMethod()
    {
        $model = new EloquentModelCastingStub;
        $model->setDateFormat('Y-m-d H:i:s');
        $model->intAttribute = '3';
        $model->floatAttribute = '4.0';
        $model->stringAttribute = 2.5;
        $model->boolAttribute = 1;
        $model->booleanAttribute = 0;
        $model->objectAttribute = ['foo' => 'bar'];
        $obj = new stdClass;
        $obj->foo = 'bar';
        $model->arrayAttribute = $obj;
        $model->jsonAttribute = ['foo' => 'bar'];
        $model->dateAttribute = '1969-07-20';
        $model->datetimeAttribute = '1969-07-20 22:56:00';
        $model->timestampAttribute = '1969-07-20 22:56:00';
        $model->collectionAttribute = new BaseCollection;
        $model->asCustomCollectionAttribute = new CustomCollection;

        $this->assertIsInt($model->intAttribute);
        $this->assertIsFloat($model->floatAttribute);
        $this->assertIsString($model->stringAttribute);
        $this->assertIsBool($model->boolAttribute);
        $this->assertIsBool($model->booleanAttribute);
        $this->assertIsObject($model->objectAttribute);
        $this->assertIsArray($model->arrayAttribute);
        $this->assertIsArray($model->jsonAttribute);
        $this->assertTrue($model->boolAttribute);
        $this->assertFalse($model->booleanAttribute);
        $this->assertEquals($obj, $model->objectAttribute);
        $this->assertEquals(['foo' => 'bar'], $model->arrayAttribute);
        $this->assertEquals(['foo' => 'bar'], $model->jsonAttribute);
        $this->assertSame('{"foo":"bar"}', $model->jsonAttributeValue());
        $this->assertInstanceOf(Carbon::class, $model->dateAttribute);
        $this->assertInstanceOf(Carbon::class, $model->datetimeAttribute);
        $this->assertInstanceOf(BaseCollection::class, $model->collectionAttribute);
        $this->assertInstanceOf(CustomCollection::class, $model->asCustomCollectionAttribute);
        $this->assertSame('1969-07-20', $model->dateAttribute->toDateString());
        $this->assertSame('1969-07-20 22:56:00', $model->datetimeAttribute->toDateTimeString());
        $this->assertEquals(-14173440, $model->timestampAttribute);

        $arr = $model->toArray();

        $this->assertIsInt($arr['intAttribute']);
        $this->assertIsFloat($arr['floatAttribute']);
        $this->assertIsString($arr['stringAttribute']);
        $this->assertIsBool($arr['boolAttribute']);
        $this->assertIsBool($arr['booleanAttribute']);
        $this->assertIsObject($arr['objectAttribute']);
        $this->assertIsArray($arr['arrayAttribute']);
        $this->assertIsArray($arr['jsonAttribute']);
        $this->assertIsArray($arr['collectionAttribute']);
        $this->assertTrue($arr['boolAttribute']);
        $this->assertFalse($arr['booleanAttribute']);
        $this->assertEquals($obj, $arr['objectAttribute']);
        $this->assertEquals(['foo' => 'bar'], $arr['arrayAttribute']);
        $this->assertEquals(['foo' => 'bar'], $arr['jsonAttribute']);
        $this->assertSame('1969-07-20 00:00:00', $arr['dateAttribute']);
        $this->assertSame('1969-07-20 22:56:00', $arr['datetimeAttribute']);
        $this->assertEquals(-14173440, $arr['timestampAttribute']);
    }

    public function testModelDateAttributeCastingResetsTime()
    {
        $model = new EloquentModelCastingStub;
        $model->setDateFormat('Y-m-d H:i:s');
        $model->dateAttribute = '1969-07-20 22:56:00';

        $this->assertSame('1969-07-20 00:00:00', $model->dateAttribute->toDateTimeString());

        $arr = $model->toArray();
        $this->assertSame('1969-07-20 00:00:00', $arr['dateAttribute']);
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
        $model->collectionAttribute = null;

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
        $this->assertNull($attributes['collectionAttribute']);

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
        $this->assertNull($model->collectionAttribute);

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
        $this->assertNull($attributes['collectionAttribute']);
    }

    public function testModelAttributeCastingFailsOnUnencodableData()
    {
        $this->expectException(JsonEncodingException::class);
        $this->expectExceptionMessage('Unable to encode attribute [objectAttribute] for model [Illuminate\Tests\Database\EloquentModelCastingStub] to JSON: Malformed UTF-8 characters, possibly incorrectly encoded.');

        $model = new EloquentModelCastingStub;
        $model->objectAttribute = ['foo' => "b\xF8r"];
        $obj = new stdClass;
        $obj->foo = "b\xF8r";
        $model->arrayAttribute = $obj;
        $model->jsonAttribute = ['foo' => "b\xF8r"];

        $model->getAttributes();
    }

    public function testModelAttributeCastingWithFloats()
    {
        $model = new EloquentModelCastingStub;

        $model->floatAttribute = 0;
        $this->assertSame(0.0, $model->floatAttribute);

        $model->floatAttribute = 'Infinity';
        $this->assertSame(INF, $model->floatAttribute);

        $model->floatAttribute = INF;
        $this->assertSame(INF, $model->floatAttribute);

        $model->floatAttribute = '-Infinity';
        $this->assertSame(-INF, $model->floatAttribute);

        $model->floatAttribute = -INF;
        $this->assertSame(-INF, $model->floatAttribute);

        $model->floatAttribute = 'NaN';
        $this->assertNan($model->floatAttribute);

        $model->floatAttribute = NAN;
        $this->assertNan($model->floatAttribute);
    }

    public function testModelAttributeCastingWithArrays()
    {
        $model = new EloquentModelCastingStub;

        $model->asEnumArrayObjectAttribute = ['draft', 'pending'];
        $this->assertInstanceOf(ArrayObject::class, $model->asEnumArrayObjectAttribute);
    }

    public function testMergeCastsMergesCasts()
    {
        $model = new EloquentModelCastingStub;

        $castCount = count($model->getCasts());
        $this->assertArrayNotHasKey('foo', $model->getCasts());

        $model->mergeCasts(['foo' => 'date']);
        $this->assertCount($castCount + 1, $model->getCasts());
        $this->assertArrayHasKey('foo', $model->getCasts());
    }

    public function testMergeCastsMergesCastsUsingArrays()
    {
        $model = new EloquentModelCastingStub;

        $castCount = count($model->getCasts());
        $this->assertArrayNotHasKey('foo', $model->getCasts());

        $model->mergeCasts([
            'foo' => ['MyClass', 'myArgumentA'],
            'bar' => ['MyClass', 'myArgumentA', 'myArgumentB'],
        ]);

        $this->assertCount($castCount + 2, $model->getCasts());
        $this->assertArrayHasKey('foo', $model->getCasts());
        $this->assertEquals($model->getCasts()['foo'], 'MyClass:myArgumentA');
        $this->assertEquals($model->getCasts()['bar'], 'MyClass:myArgumentA,myArgumentB');
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
        $model = m::mock(EloquentModelStub::class.'[delete,getRelationValue]');
        $model->name = 'Spark';
        $model->shouldNotReceive('delete');
        $model->shouldReceive('getRelationValue')->once()->with('belongsToStub')->andReturn('relation');

        // Can return a normal relation
        $this->assertSame('relation', $model->belongsToStub);

        // Can return a normal attribute
        $this->assertSame('Spark', $model->name);

        // Returns null for a Model.php method name
        $this->assertNull($model->delete);

        $model = m::mock(EloquentModelStub::class.'[delete]');
        $model->delete = 123;
        $this->assertEquals(123, $model->delete);
    }

    public function testIntKeyTypePreserved()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();
        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with([], 'id')->andReturn(1);
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);

        $this->assertTrue($model->save());
        $this->assertEquals(1, $model->id);
    }

    public function testStringKeyTypePreserved()
    {
        $model = $this->getMockBuilder(EloquentKeyTypeModelStub::class)->onlyMethods(['newModelQuery', 'updateTimestamps', 'refresh'])->getMock();

        $query = m::mock(Builder::class);
        $query->shouldReceive('insertGetId')->once()->with([], 'id')->andReturn('string id');
        $query->shouldReceive('getConnection')->once();
        $model->expects($this->once())->method('newModelQuery')->willReturn($query);

        $this->assertTrue($model->save());
        $this->assertSame('string id', $model->id);
    }

    public function testScopesMethod()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        $scopes = [
            'published',
            'category' => 'Laravel',
            'framework' => ['Laravel', '5.3'],
            'date' => Carbon::now(),
        ];

        $this->assertInstanceOf(Builder::class, $model->scopes($scopes));
        $this->assertSame($scopes, $model->scopesCalled);
    }

    public function testScopesMethodWithString()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        $this->assertInstanceOf(Builder::class, $model->scopes('published'));
        $this->assertSame(['published'], $model->scopesCalled);
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

    public function testWithoutTouchingCallback()
    {
        new EloquentModelStub(['id' => 1]);

        $called = false;

        EloquentModelStub::withoutTouching(function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testWithoutTouchingOnCallback()
    {
        new EloquentModelStub(['id' => 1]);

        $called = false;

        Model::withoutTouchingOn([EloquentModelStub::class], function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testThrowsWhenAccessingMissingAttributes()
    {
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        try {
            $model = new EloquentModelStub(['id' => 1]);
            $model->exists = true;

            $this->assertEquals(1, $model->id);
            $this->expectException(MissingAttributeException::class);

            $model->this_attribute_does_not_exist;
        } finally {
            Model::preventAccessingMissingAttributes($originalMode);
        }
    }

    public function testThrowsWhenAccessingMissingAttributesWhichArePrimitiveCasts()
    {
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        $model = new EloquentModelWithPrimitiveCasts(['id' => 1]);
        $model->exists = true;

        $exceptionCount = 0;
        $primitiveCasts = EloquentModelWithPrimitiveCasts::makePrimitiveCastsArray();
        try {
            try {
                $this->assertEquals(null, $model->backed_enum);
            } catch (MissingAttributeException) {
                $exceptionCount++;
            }

            foreach ($primitiveCasts as $key => $type) {
                try {
                    $v = $model->{$key};
                } catch (MissingAttributeException) {
                    $exceptionCount++;
                }
            }

            $this->assertInstanceOf(Address::class, $model->address);

            $this->assertEquals(1, $model->id);
            $this->assertEquals('ok', $model->this_is_fine);
            $this->assertEquals('ok', $model->this_is_also_fine);

            // Primitive castables, enum castable
            $expectedExceptionCount = count($primitiveCasts) + 1;
            $this->assertEquals($expectedExceptionCount, $exceptionCount);
        } finally {
            Model::preventAccessingMissingAttributes($originalMode);
        }
    }

    public function testUsesOverriddenHandlerWhenAccessingMissingAttributes()
    {
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        $callbackModel = null;
        $callbackKey = null;

        Model::handleMissingAttributeViolationUsing(function ($model, $key) use (&$callbackModel, &$callbackKey) {
            $callbackModel = $model;
            $callbackKey = $key;
        });

        $model = new EloquentModelStub(['id' => 1]);
        $model->exists = true;

        $this->assertEquals(1, $model->id);

        $model->this_attribute_does_not_exist;

        $this->assertInstanceOf(EloquentModelStub::class, $callbackModel);
        $this->assertEquals('this_attribute_does_not_exist', $callbackKey);

        Model::preventAccessingMissingAttributes($originalMode);
        Model::handleMissingAttributeViolationUsing(null);
    }

    public function testDoesntThrowWhenAccessingMissingAttributesOnModelThatIsNotSaved()
    {
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        try {
            $model = new EloquentModelStub(['id' => 1]);
            $model->exists = false;

            $this->assertEquals(1, $model->id);
            $this->assertNull($model->this_attribute_does_not_exist);
        } finally {
            Model::preventAccessingMissingAttributes($originalMode);
        }
    }

    public function testDoesntThrowWhenAccessingMissingAttributesOnModelThatWasRecentlyCreated()
    {
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        try {
            $model = new EloquentModelStub(['id' => 1]);
            $model->exists = true;
            $model->wasRecentlyCreated = true;

            $this->assertEquals(1, $model->id);
            $this->assertNull($model->this_attribute_does_not_exist);
        } finally {
            Model::preventAccessingMissingAttributes($originalMode);
        }
    }

    public function testDoesntThrowWhenAssigningMissingAttributes()
    {
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        try {
            $model = new EloquentModelStub(['id' => 1]);
            $model->exists = true;

            $model->this_attribute_does_not_exist = 'now it does';
        } finally {
            Model::preventAccessingMissingAttributes($originalMode);
        }
    }

    public function testDoesntThrowWhenTestingMissingAttributes()
    {
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        try {
            $model = new EloquentModelStub(['id' => 1]);
            $model->exists = true;

            $this->assertTrue(isset($model->id));
            $this->assertFalse(isset($model->this_attribute_does_not_exist));
        } finally {
            Model::preventAccessingMissingAttributes($originalMode);
        }
    }

    protected function addMockConnection($model)
    {
        $model->setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));
        $resolver->shouldReceive('connection')->andReturn($connection = m::mock(Connection::class));
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar = m::mock(Grammar::class));
        $grammar->shouldReceive('getBitwiseOperators')->andReturn([]);
        $grammar->shouldReceive('isExpression')->andReturnFalse();
        $connection->shouldReceive('getPostProcessor')->andReturn($processor = m::mock(Processor::class));
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new BaseBuilder($connection, $grammar, $processor);
        });
    }

    public function testTouchingModelWithTimestamps()
    {
        $this->assertFalse(
            Model::isIgnoringTouch(Model::class)
        );
    }

    public function testNotTouchingModelWithUpdatedAtNull()
    {
        $this->assertTrue(
            Model::isIgnoringTouch(EloquentModelWithUpdatedAtNull::class)
        );
    }

    public function testNotTouchingModelWithoutTimestamps()
    {
        $this->assertTrue(
            Model::isIgnoringTouch(EloquentModelWithoutTimestamps::class)
        );
    }

    public function testGetOriginalCastsAttributes()
    {
        $model = new EloquentModelCastingStub;
        $model->intAttribute = '1';
        $model->floatAttribute = '0.1234';
        $model->stringAttribute = 432;
        $model->boolAttribute = '1';
        $model->booleanAttribute = '0';
        $stdClass = new stdClass;
        $stdClass->json_key = 'json_value';
        $model->objectAttribute = $stdClass;
        $array = [
            'foo' => 'bar',
        ];
        $collection = collect($array);
        $model->arrayAttribute = $array;
        $model->jsonAttribute = $array;
        $model->collectionAttribute = $collection;

        $model->syncOriginal();

        $model->intAttribute = 2;
        $model->floatAttribute = 0.443;
        $model->stringAttribute = '12';
        $model->boolAttribute = true;
        $model->booleanAttribute = false;
        $model->objectAttribute = $stdClass;
        $model->arrayAttribute = [
            'foo' => 'bar2',
        ];
        $model->jsonAttribute = [
            'foo' => 'bar2',
        ];
        $model->collectionAttribute = collect([
            'foo' => 'bar2',
        ]);

        $this->assertIsInt($model->getOriginal('intAttribute'));
        $this->assertEquals(1, $model->getOriginal('intAttribute'));
        $this->assertEquals(2, $model->intAttribute);
        $this->assertEquals(2, $model->getAttribute('intAttribute'));

        $this->assertIsFloat($model->getOriginal('floatAttribute'));
        $this->assertEquals(0.1234, $model->getOriginal('floatAttribute'));
        $this->assertEquals(0.443, $model->floatAttribute);

        $this->assertIsString($model->getOriginal('stringAttribute'));
        $this->assertSame('432', $model->getOriginal('stringAttribute'));
        $this->assertSame('12', $model->stringAttribute);

        $this->assertIsBool($model->getOriginal('boolAttribute'));
        $this->assertTrue($model->getOriginal('boolAttribute'));
        $this->assertTrue($model->boolAttribute);

        $this->assertIsBool($model->getOriginal('booleanAttribute'));
        $this->assertFalse($model->getOriginal('booleanAttribute'));
        $this->assertFalse($model->booleanAttribute);

        $this->assertEquals($stdClass, $model->getOriginal('objectAttribute'));
        $this->assertEquals($model->getAttribute('objectAttribute'), $model->getOriginal('objectAttribute'));

        $this->assertEquals($array, $model->getOriginal('arrayAttribute'));
        $this->assertEquals(['foo' => 'bar'], $model->getOriginal('arrayAttribute'));
        $this->assertEquals(['foo' => 'bar2'], $model->getAttribute('arrayAttribute'));

        $this->assertEquals($array, $model->getOriginal('jsonAttribute'));
        $this->assertEquals(['foo' => 'bar'], $model->getOriginal('jsonAttribute'));
        $this->assertEquals(['foo' => 'bar2'], $model->getAttribute('jsonAttribute'));

        $this->assertEquals(['foo' => 'bar'], $model->getOriginal('collectionAttribute')->toArray());
        $this->assertEquals(['foo' => 'bar2'], $model->getAttribute('collectionAttribute')->toArray());
    }

    public function testCastsMethodHasPriorityOverCastsProperty()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'duplicatedAttribute' => '1',
        ], true);

        $this->assertIsInt($model->duplicatedAttribute);
        $this->assertEquals(1, $model->duplicatedAttribute);
        $this->assertEquals(1, $model->getAttribute('duplicatedAttribute'));
    }

    public function testCastsMethodIsTakenInConsiderationOnSerialization()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'duplicatedAttribute' => '1',
        ], true);

        $model = unserialize(serialize($model));

        $this->assertIsInt($model->duplicatedAttribute);
        $this->assertEquals(1, $model->duplicatedAttribute);
        $this->assertEquals(1, $model->getAttribute('duplicatedAttribute'));
    }

    public function testsCastOnArrayFormatWithOneElement()
    {
        $model = new EloquentModelCastingStub;
        $model->setRawAttributes([
            'singleElementInArrayAttribute' => '{"bar": "foo"}',
        ]);
        $model->syncOriginal();

        $this->assertInstanceOf(BaseCollection::class, $model->singleElementInArrayAttribute);
        $this->assertEquals(['bar' => 'foo'], $model->singleElementInArrayAttribute->toArray());
        $this->assertEquals(['bar' => 'foo'], $model->getAttribute('singleElementInArrayAttribute')->toArray());
    }

    public function testUnsavedModel()
    {
        $user = new UnsavedModel;
        $user->name = null;

        $this->assertNull($user->name);
    }

    public function testDiscardChanges()
    {
        $user = new EloquentModelStub([
            'name' => 'Taylor Otwell',
        ]);

        $this->assertNotEmpty($user->isDirty());
        $this->assertNull($user->getOriginal('name'));
        $this->assertSame('Taylor Otwell', $user->getAttribute('name'));

        $user->discardChanges();

        $this->assertEmpty($user->isDirty());
        $this->assertNull($user->getOriginal('name'));
        $this->assertNull($user->getAttribute('name'));
    }

    public function testHasAttribute()
    {
        $user = new EloquentModelStub([
            'name' => 'Mateus',
        ]);

        $this->assertTrue($user->hasAttribute('name'));
        $this->assertTrue($user->hasAttribute('password'));
        $this->assertTrue($user->hasAttribute('castedFloat'));
        $this->assertFalse($user->hasAttribute('nonexistent'));
        $this->assertFalse($user->hasAttribute('belongsToStub'));
    }

    public function testModelToJsonSucceedsWithPriorErrors(): void
    {
        $user = new EloquentModelStub(['name' => 'Mateus']);

        // Simulate a JSON error
        json_decode('{');
        $this->assertTrue(json_last_error() !== JSON_ERROR_NONE);

        $this->assertSame('{"name":"Mateus"}', $user->toJson(JSON_THROW_ON_ERROR));
    }

    public function testFillableWithMutators()
    {
        $model = new EloquentModelWithMutators;
        $model->fillable(['full_name', 'full_address']);
        $model->fill(['id' => 1, 'full_name' => 'John Doe', 'full_address' => '123 Main Street, Anytown']);

        $this->assertNull($model->id);
        $this->assertSame('John', $model->first_name);
        $this->assertSame('Doe', $model->last_name);
        $this->assertSame('123 Main Street', $model->address_line_one);
        $this->assertSame('Anytown', $model->address_line_two);
    }

    public function testGuardedWithMutators()
    {
        $model = new EloquentModelWithMutators;
        $model->guard(['id']);
        $model->fill(['id' => 1, 'full_name' => 'John Doe', 'full_address' => '123 Main Street, Anytown']);

        $this->assertNull($model->id);
        $this->assertSame('John', $model->first_name);
        $this->assertSame('Doe', $model->last_name);
        $this->assertSame('123 Main Street', $model->address_line_one);
        $this->assertSame('Anytown', $model->address_line_two);
    }

    public function testCollectedByAttribute()
    {
        $model = new EloquentModelWithCollectedByAttribute;
        $collection = $model->newCollection([$model]);

        $this->assertInstanceOf(CustomEloquentCollection::class, $collection);
    }

    public function testUseFactoryAttribute()
    {
        $model = new EloquentModelWithUseFactoryAttribute;
        $instance = EloquentModelWithUseFactoryAttribute::factory()->make(['name' => 'test name']);
        $factory = EloquentModelWithUseFactoryAttribute::factory();
        $this->assertInstanceOf(EloquentModelWithUseFactoryAttribute::class, $instance);
        $this->assertInstanceOf(EloquentModelWithUseFactoryAttributeFactory::class, $model::factory());
        $this->assertInstanceOf(EloquentModelWithUseFactoryAttributeFactory::class, $model::newFactory());
        $this->assertEquals(EloquentModelWithUseFactoryAttribute::class, $factory->modelName());
        $this->assertEquals('test name', $instance->name); // Small smoke test to ensure the factory is working
    }
}

class EloquentTestObserverStub
{
    public function creating()
    {
        //
    }

    public function saved()
    {
        //
    }
}

class EloquentTestAnotherObserverStub
{
    public function creating()
    {
        //
    }

    public function saved()
    {
        //
    }
}

class EloquentTestThirdObserverStub
{
    public function creating()
    {
        //
    }

    public function saved()
    {
        //
    }
}

class EloquentModelStub extends Model
{
    public $connection;
    public $scopesCalled = [];
    protected $table = 'stub';
    protected $guarded = [];
    protected $casts = ['castedFloat' => 'float'];

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

    public function publicIncrement($column, $amount = 1, $extra = [])
    {
        return $this->increment($column, $amount, $extra);
    }

    public function publicIncrementQuietly($column, $amount = 1, $extra = [])
    {
        return $this->incrementQuietly($column, $amount, $extra);
    }

    public function publicDecrementQuietly($column, $amount = 1, $extra = [])
    {
        return $this->decrementQuietly($column, $amount, $extra);
    }

    public function belongsToStub()
    {
        return $this->belongsTo(EloquentModelSaveStub::class);
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
        return $this->belongsTo(EloquentModelSaveStub::class, 'foo');
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

    public function scopeDate(Builder $builder, Carbon $date)
    {
        $this->scopesCalled['date'] = $date;
    }
}

trait FooBarTrait
{
    public $fooBarIsInitialized = false;

    public function initializeFooBarTrait()
    {
        $this->fooBarIsInitialized = true;
    }
}

class EloquentModelStubWithTrait extends EloquentModelStub
{
    use FooBarTrait;
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
    protected $guarded = [];

    public function save(array $options = [])
    {
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        $_SERVER['__eloquent.saved'] = true;

        $this->fireModelEvent('saved', false);
    }

    public function setIncrementing($value)
    {
        $this->incrementing = $value;
    }

    public function getConnection()
    {
        $mock = m::mock(Connection::class);
        $mock->shouldReceive('getQueryGrammar')->andReturn($grammar = m::mock(Grammar::class));
        $grammar->shouldReceive('getBitwiseOperators')->andReturn([]);
        $grammar->shouldReceive('isExpression')->andReturnFalse();
        $mock->shouldReceive('getPostProcessor')->andReturn($processor = m::mock(Processor::class));
        $mock->shouldReceive('getName')->andReturn('name');
        $mock->shouldReceive('query')->andReturnUsing(function () use ($mock, $grammar, $processor) {
            return new BaseBuilder($mock, $grammar, $processor);
        });

        return $mock;
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
        $mock = m::mock(Builder::class);
        $mock->shouldReceive('useWritePdo')->once()->andReturnSelf();
        $mock->shouldReceive('find')->once()->with(1)->andReturn('foo');

        return $mock;
    }
}

class EloquentModelDestroyStub extends Model
{
    protected $fillable = [
        'id',
    ];

    public function newQuery()
    {
        $mock = m::mock(Builder::class);
        $mock->shouldReceive('whereIn')->once()->with('id', [1, 2, 3])->andReturn($mock);
        $mock->shouldReceive('get')->once()->andReturn([$model = m::mock(stdClass::class)]);
        $model->shouldReceive('delete')->once();

        return $mock;
    }
}

class EloquentModelEmptyDestroyStub extends Model
{
    public function newQuery()
    {
        $mock = m::mock(Builder::class);
        $mock->shouldReceive('whereIn')->never();

        return $mock;
    }
}

class EloquentModelWithStub extends Model
{
    public function newQuery()
    {
        $mock = m::mock(Builder::class);
        $mock->shouldReceive('with')->once()->with(['foo', 'bar'])->andReturn('foo');

        return $mock;
    }
}

class EloquentModelWithWhereHasStub extends Model
{
    public function foo()
    {
        return $this->hasMany(EloquentModelStub::class);
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
    //
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
        //
    }

    public function getMiddleNameAttribute()
    {
        //
    }

    public function getLastNameAttribute()
    {
        //
    }

    public function doNotgetFirstInvalidAttribute()
    {
        //
    }

    public function doNotGetSecondInvalidAttribute()
    {
        //
    }

    public function doNotgetThirdInvalidAttributeEither()
    {
        //
    }

    public function doNotGetFourthInvalidAttributeEither()
    {
        //
    }
}

class EloquentModelCastingStub extends Model
{
    protected $casts = [
        'floatAttribute' => 'float',
        'boolAttribute' => 'bool',
        'objectAttribute' => 'object',
        'jsonAttribute' => 'json',
        'dateAttribute' => 'date',
        'timestampAttribute' => 'timestamp',
        'ascollectionAttribute' => AsCollection::class,
        'asCustomCollectionAsArrayAttribute' => [AsCollection::class, CustomCollection::class],
        'asEncryptedCollectionAttribute' => AsEncryptedCollection::class,
        'asEnumCollectionAttribute' => AsEnumCollection::class.':'.StringStatus::class,
        'asEnumArrayObjectAttribute' => AsEnumArrayObject::class.':'.StringStatus::class,
        'duplicatedAttribute' => 'string',
    ];

    protected function casts(): array
    {
        return [
            'intAttribute' => 'int',
            'stringAttribute' => 'string',
            'booleanAttribute' => 'boolean',
            'arrayAttribute' => 'array',
            'collectionAttribute' => 'collection',
            'datetimeAttribute' => 'datetime',
            'asarrayobjectAttribute' => AsArrayObject::class,
            'asStringableAttribute' => AsStringable::class,
            'asCustomCollectionAttribute' => AsCollection::using(CustomCollection::class),
            'asEncryptedArrayObjectAttribute' => AsEncryptedArrayObject::class,
            'asEncryptedCustomCollectionAttribute' => AsEncryptedCollection::using(CustomCollection::class),
            'asEncryptedCustomCollectionAsArrayAttribute' => [AsEncryptedCollection::class, CustomCollection::class],
            'asCustomEnumCollectionAttribute' => AsEnumCollection::of(StringStatus::class),
            'asCustomEnumArrayObjectAttribute' => AsEnumArrayObject::of(StringStatus::class),
            'singleElementInArrayAttribute' => [AsCollection::class],
            'duplicatedAttribute' => 'int',
        ];
    }

    public function jsonAttributeValue()
    {
        return $this->attributes['jsonAttribute'];
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

class EloquentModelEnumCastingStub extends Model
{
    protected $casts = ['enumAttribute' => StringStatus::class];
}

class EloquentModelDynamicHiddenStub extends Model
{
    protected $table = 'stub';
    protected $guarded = [];

    public function getHidden()
    {
        return ['age', 'id'];
    }
}

class EloquentModelDynamicVisibleStub extends Model
{
    protected $table = 'stub';
    protected $guarded = [];

    public function getVisible()
    {
        return ['name', 'id'];
    }
}

class EloquentModelNonIncrementingStub extends Model
{
    protected $table = 'stub';
    protected $guarded = [];
    public $incrementing = false;
}

class EloquentNoConnectionModelStub extends EloquentModelStub
{
    //
}

class EloquentDifferentConnectionModelStub extends EloquentModelStub
{
    public $connection = 'different_connection';
}

class EloquentPrimaryUuidModelStub extends EloquentModelStub
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public function getKeyName()
    {
        return 'uuid';
    }
}

class EloquentNonPrimaryUuidModelStub extends EloquentModelStub
{
    use HasUuids;

    public function getKeyName()
    {
        return 'id';
    }

    public function uniqueIds()
    {
        return ['uuid'];
    }
}

class EloquentPrimaryUlidModelStub extends EloquentModelStub
{
    use HasUlids;

    public $incrementing = false;
    protected $keyType = 'string';

    public function getKeyName()
    {
        return 'ulid';
    }
}

class EloquentNonPrimaryUlidModelStub extends EloquentModelStub
{
    use HasUlids;

    public function getKeyName()
    {
        return 'id';
    }

    public function uniqueIds()
    {
        return ['ulid'];
    }
}

#[ObservedBy(EloquentTestObserverStub::class)]
class EloquentModelWithObserveAttributeStub extends EloquentModelStub
{
    //
}

#[ObservedBy([EloquentTestObserverStub::class])]
class EloquentModelWithObserveAttributeUsingArrayStub extends EloquentModelStub
{
    //
}

#[ObservedBy([EloquentTestObserverStub::class])]
class EloquentModelWithObserveAttributeGrandparentStub extends EloquentModelStub
{
    //
}

#[ObservedBy([EloquentTestAnotherObserverStub::class])]
class EloquentModelWithObserveAttributeParentStub extends EloquentModelWithObserveAttributeGrandparentStub
{
    //
}

#[ObservedBy([EloquentTestThirdObserverStub::class])]
class EloquentModelWithObserveAttributeGrandchildStub extends EloquentModelWithObserveAttributeParentStub
{
    //
}

class EloquentModelSavingEventStub
{
    //
}

class EloquentModelEventObjectStub extends Model
{
    protected $dispatchesEvents = [
        'saving' => EloquentModelSavingEventStub::class,
    ];
}

class EloquentModelWithoutTimestamps extends Model
{
    protected $table = 'stub';
    public $timestamps = false;
}

class EloquentModelWithUpdatedAtNull extends Model
{
    protected $table = 'stub';
    const UPDATED_AT = null;
}

class UnsavedModel extends Model
{
    protected $casts = ['name' => Uppercase::class];
}

class Uppercase implements CastsInboundAttributes
{
    public function set($model, string $key, $value, array $attributes)
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}

class CustomCollection extends BaseCollection
{
    //
}

class EloquentModelWithPrimitiveCasts extends Model
{
    public $fillable = ['id'];

    public $casts = [
        'backed_enum' => CastableBackedEnum::class,
        'address' => Address::class,
    ];

    public $attributes = [
        'address_line_one' => null,
        'address_line_two' => null,
    ];

    public static function makePrimitiveCastsArray(): array
    {
        $toReturn = [];

        foreach (static::$primitiveCastTypes as $index => $primitiveCastType) {
            $toReturn['primitive_cast_'.$index] = $primitiveCastType;
        }

        return $toReturn;
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeCasts(self::makePrimitiveCastsArray());
    }

    public function getThisIsFineAttribute($value)
    {
        return 'ok';
    }

    public function thisIsAlsoFine(): Attribute
    {
        return Attribute::get(fn () => 'ok');
    }
}

enum CastableBackedEnum: string
{
    case Value1 = 'value1';
}

class Address implements Castable
{
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get(Model $model, string $key, mixed $value, array $attributes): Address
            {
                return new Address(
                    $attributes['address_line_one'],
                    $attributes['address_line_two']
                );
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): array
            {
                return [
                    'address_line_one' => $value->lineOne ?? null,
                    'address_line_two' => $value->lineTwo ?? null,
                ];
            }
        };
    }
}

class EloquentModelWithRecursiveRelationshipsStub extends Model
{
    public $fillable = ['id', 'parent_id'];

    protected static \WeakMap $recursionDetectionCache;

    public function getQueueableRelations()
    {
        try {
            $this->stepIn();

            return parent::getQueueableRelations();
        } finally {
            $this->stepOut();
        }
    }

    public function push()
    {
        try {
            $this->stepIn();

            return parent::push();
        } finally {
            $this->stepOut();
        }
    }

    public function save(array $options = [])
    {
        return true;
    }

    public function relationsToArray()
    {
        try {
            $this->stepIn();

            return parent::relationsToArray();
        } finally {
            $this->stepOut();
        }
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function self(): BelongsTo
    {
        return $this->belongsTo(static::class, 'id');
    }

    protected static function getRecursionDetectionCache()
    {
        return static::$recursionDetectionCache ??= new \WeakMap;
    }

    protected function getRecursionDepth(): int
    {
        $cache = static::getRecursionDetectionCache();

        return $cache->offsetExists($this) ? $cache->offsetGet($this) : 0;
    }

    protected function stepIn(): void
    {
        $depth = $this->getRecursionDepth();

        if ($depth > 1) {
            throw new \RuntimeException('Recursion detected');
        }
        static::getRecursionDetectionCache()->offsetSet($this, $depth + 1);
    }

    protected function stepOut(): void
    {
        $cache = static::getRecursionDetectionCache();
        if ($depth = $this->getRecursionDepth()) {
            $cache->offsetSet($this, $depth - 1);
        } else {
            $cache->offsetUnset($this);
        }
    }
}

class EloquentModelWithMutators extends Model
{
    public $attributes = [
        'first_name' => null,
        'last_name' => null,
        'address_line_one' => null,
        'address_line_two' => null,
    ];

    protected function fullName(): Attribute
    {
        return Attribute::make(
            set: function (string $fullName) {
                [$firstName, $lastName] = explode(' ', $fullName);

                return [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ];
            }
        );
    }

    public function setFullAddressAttribute($fullAddress)
    {
        [$addressLineOne, $addressLineTwo] = explode(', ', $fullAddress);

        $this->attributes['address_line_one'] = $addressLineOne;
        $this->attributes['address_line_two'] = $addressLineTwo;
    }
}

#[CollectedBy(CustomEloquentCollection::class)]
class EloquentModelWithCollectedByAttribute extends Model
{
}

class CustomEloquentCollection extends Collection
{
}

class EloquentModelWithUseFactoryAttributeFactory extends Factory
{
    public function definition()
    {
        return [];
    }
}

#[UseFactory(EloquentModelWithUseFactoryAttributeFactory::class)]
class EloquentModelWithUseFactoryAttribute extends Model
{
    use HasFactory;
}
