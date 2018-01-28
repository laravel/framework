<?php

namespace Illuminate\Tests\Support;

use DateTime;
use stdClass;
use Mockery as m;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Model;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\InteractsWithTime;

class SupportModelTest extends TestCase
{
    use InteractsWithTime;

    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
    }

    public function tearDown()
    {
        parent::tearDown();

        m::close();
        Carbon::setTestNow(null);
        \Illuminate\Support\Carbon::resetToStringFormat();
    }

    public function testAttributeManipulation()
    {
        $model = new SupportModelStub;
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
        $model = new SupportModelStub(['foo' => '1', 'bar' => 2, 'baz' => 3]);
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

    public function testDirtyOnCastOrDateAttributes()
    {
        $model = new SupportModelCastingStub;
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

    public function testCleanAttributes()
    {
        $model = new SupportModelStub(['foo' => '1', 'bar' => 2, 'baz' => 3]);
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
        $model = new SupportModelStub;
        $model->password = 'secret';
        $attributes = $model->getAttributes();

        // ensure password attribute was not set to null
        $this->assertArrayNotHasKey('password', $attributes);
        $this->assertEquals('******', $model->password);

        $hash = 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4';

        $this->assertEquals($hash, $attributes['password_hash']);
        $this->assertEquals($hash, $model->password_hash);
    }

    public function testOnly()
    {
        $model = new SupportModelStub;
        $model->first_name = 'taylor';
        $model->last_name = 'otwell';
        $model->project = 'laravel';

        $this->assertEquals(['project' => 'laravel'], $model->only('project'));
        $this->assertEquals(['first_name' => 'taylor', 'last_name' => 'otwell'], $model->only('first_name', 'last_name'));
        $this->assertEquals(['first_name' => 'taylor', 'last_name' => 'otwell'], $model->only(['first_name', 'last_name']));
    }

    public function testTimestampsAreCreatedFromStringsAndIntegers()
    {
        $model = new SupportDateModelStub;
        $model->created_at = '2013-05-22 00:00:00';
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->created_at);

        $model = new SupportDateModelStub;
        $model->created_at = $this->currentTime();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->created_at);

        $model = new SupportDateModelStub;
        $model->created_at = 0;
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->created_at);

        $model = new SupportDateModelStub;
        $model->created_at = '2012-01-01';
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->created_at);
    }

    public function testFromDateTime()
    {
        $model = new SupportModelStub;

        $value = \Illuminate\Support\Carbon::parse('2015-04-17 22:59:01');
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

    public function testToArray()
    {
        $model = new SupportModelStub;
        $model->name = 'foo';
        $model->age = null;
        $model->password = 'password1';
        $model->setHidden(['password']);
        $array = $model->toArray();

        $this->assertInternalType('array', $array);
        $this->assertFalse(isset($array['password']));

        $model->setAppends(['appendable']);
        $array = $model->toArray();
        $this->assertEquals('appended', $array['appendable']);
    }

    public function testVisibleCreatesArrayWhitelist()
    {
        $model = new SupportModelStub;
        $model->setVisible(['name']);
        $model->name = 'Taylor';
        $model->age = 26;
        $array = $model->toArray();

        $this->assertEquals(['name' => 'Taylor'], $array);
    }

    public function testToArrayUsesMutators()
    {
        $model = new SupportModelStub;
        $model->list_items = [1, 2, 3];
        $array = $model->toArray();

        $this->assertEquals([1, 2, 3], $array['list_items']);
    }

    public function testHidden()
    {
        $model = new SupportModelStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $model->setHidden(['age', 'id']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testVisible()
    {
        $model = new SupportModelStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $model->setVisible(['name', 'id']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testDynamicHidden()
    {
        $model = new SupportModelDynamicHiddenStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testWithHidden()
    {
        $model = new SupportModelStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $model->setHidden(['age', 'id']);
        $model->makeVisible('age');
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertArrayNotHasKey('id', $array);
    }

    public function testMakeHidden()
    {
        $model = new SupportModelStub(['name' => 'foo', 'age' => 'bar', 'address' => 'foobar', 'id' => 'baz']);
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
        $model = new SupportModelDynamicVisibleStub(['name' => 'foo', 'age' => 'bar', 'id' => 'baz']);
        $array = $model->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('age', $array);
    }

    public function testFillingJSONAttributes()
    {
        $model = new SupportModelStub;
        $model->fill(['meta->name' => 'foo', 'meta->price' => 'bar', 'meta->size->width' => 'baz']);
        $this->assertEquals(
            ['meta' => json_encode(['name' => 'foo', 'price' => 'bar', 'size' => ['width' => 'baz']])],
            $model->toArray()
        );

        $model = new SupportModelStub(['meta' => json_encode(['name' => 'Taylor'])]);
        $model->fill(['meta->name' => 'foo', 'meta->price' => 'bar', 'meta->size->width' => 'baz']);
        $this->assertEquals(
            ['meta' => json_encode(['name' => 'foo', 'price' => 'bar', 'size' => ['width' => 'baz']])],
            $model->toArray()
        );
    }

    public function testUnderscorePropertiesAreFilled()
    {
        $model = new SupportModelStub;
        $attributes = ['_method' => 'PUT'];
        $model->fill($attributes);
        $this->assertEquals($attributes, $model->getAttributes());
    }

    public function testTheMutatorCacheIsPopulated()
    {
        $class = new SupportModelStub;

        $expectedAttributes = [
            'list_items',
            'password',
            'appendable',
        ];

        $this->assertEquals($expectedAttributes, $class->getMutatedAttributes());
    }

    public function testAppendingOfAttributes()
    {
        $model = new SupportModelAppendsStub;

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
        $model = new SupportModelGetMutatorsStub;

        $this->assertEquals(['first_name', 'middle_name', 'last_name'], $model->getMutatedAttributes());

        SupportModelGetMutatorsStub::resetMutatorCache();

        SupportModelGetMutatorsStub::$snakeAttributes = false;
        $this->assertEquals(['firstName', 'middleName', 'lastName'], $model->getMutatedAttributes());
    }

    public function testModelAttributesAreCastedWhenPresentInCastsArray()
    {
        $model = new SupportModelCastingStub;
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
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->dateAttribute);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->datetimeAttribute);
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
        $model = new SupportModelCastingStub;
        $model->setDateFormat('Y-m-d H:i:s');
        $model->dateAttribute = '1969-07-20 22:56:00';

        $this->assertEquals('1969-07-20 00:00:00', $model->dateAttribute->toDateTimeString());

        $arr = $model->toArray();
        $this->assertEquals('1969-07-20 00:00:00', $arr['dateAttribute']);
    }

    public function testModelAttributeCastingPreservesNull()
    {
        $model = new SupportModelCastingStub;
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
     * @expectedException \Illuminate\Support\JsonEncodingException
     * @expectedExceptionMessage Unable to encode attribute [objectAttribute] for model [Illuminate\Tests\Support\SupportModelCastingStub] to JSON: Malformed UTF-8 characters, possibly incorrectly encoded.
     */
    public function testModelAttributeCastingFailsOnUnencodableData()
    {
        $model = new SupportModelCastingStub;
        $model->objectAttribute = ['foo' => "b\xF8r"];
        $obj = new stdClass;
        $obj->foo = "b\xF8r";
        $model->arrayAttribute = $obj;
        $model->jsonAttribute = ['foo' => "b\xF8r"];

        $model->getAttributes();
    }
}

class SupportModelStub extends Model
{
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

    public function getDates()
    {
        return [];
    }

    public function getAppendableAttribute()
    {
        return 'appended';
    }
}

class SupportDateModelStub extends SupportModelStub
{
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }
}

class SupportModelAppendsStub extends Model
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

class SupportModelGetMutatorsStub extends Model
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

class SupportModelCastingStub extends Model
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

class SupportModelDynamicHiddenStub extends Model
{
    public function getHidden()
    {
        return ['age', 'id'];
    }
}

class SupportModelDynamicVisibleStub extends Model
{
    public function getVisible()
    {
        return ['name', 'id'];
    }
}
