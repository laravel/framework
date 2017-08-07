<?php

namespace Illuminate\Tests\Integration\Database;

use stdClass;
use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentModelTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('test_model1', function ($table) {
            $table->increments('id');
            $table->timestamp('nullable_datetime')->nullable();
            $table->date('nullable_date')->nullable();
            $table->date('date')->nullable();
            $table->timestamp('datetime')->nullable();
            $table->timestamps();
        });

        Schema::create('test_model2', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
        });
    }

    public function test_create_saves_model()
    {
        $user = TestModel2::create([
            'name' => str_random(), 'title' => str_random(),
        ]);

        $this->assertNotNull(TestModel2::find($user->id));
    }

    public function test_make_doesnt_save_model()
    {
        $user = TestModel2::make([
            'name' => str_random(), 'title' => str_random(),
        ]);

        $this->assertNull(TestModel2::find($user->id));
    }

    public function test_force_saves_model_with_guarded()
    {
        TestModel2::forceCreate([
            'id' => 1000010,
            'name' => str_random(), 'title' => str_random(),
        ]);

        $this->assertNotNull(TestModel2::find(1000010));
    }

    public function test_timestamps_are_created_from_strings_and_integers()
    {
        Carbon::setTestNow(
            Carbon::create(2017, 10, 10, 10, 8, 0)
        );

        $model = new TestModel1();
        $model->created_at = '2013-05-22 00:00:00';
        $this->assertInstanceOf(\DateTime::class, $model->created_at);

        $model = new TestModel1();
        $model->created_at = Carbon::now();
        $this->assertInstanceOf(\DateTime::class, $model->created_at);

        $model = new TestModel1();
        $model->created_at = 0;
        $this->assertInstanceOf(\DateTime::class, $model->created_at);
    }

    public function test_model_can_cast_dates()
    {
        Carbon::setTestNow(
            Carbon::create(2017, 10, 10, 10, 8, 0)
        );

        $user = TestModel1::create([
            'nullable_date' => null,
            'nullable_datetime' => null,
            'date' => '2017-10-10',
            'datetime' => '2017-10-10 10:08:00',
        ]);

        $this->assertInstanceOf(\DateTime::class, $user->date);
        $this->assertInstanceOf(\DateTime::class, $user->datetime);
        $this->assertInstanceOf(\DateTime::class, $user->created_at);
        $this->assertInstanceOf(\DateTime::class, $user->updated_at);
        $this->assertNull($user->nullable_date);
        $this->assertNull($user->nullable_datetime);

        $userAsArray = $user->toArray();

        $this->assertSame('2017-10-10', $userAsArray['date']);
        $this->assertSame('2017-10-10 10:08:00', $userAsArray['datetime']);
        $this->assertSame('2017-10-10 10:08:00', $userAsArray['created_at']);
        $this->assertSame('2017-10-10 10:08:00', $userAsArray['updated_at']);
        $this->assertNull($userAsArray['nullable_date']);
        $this->assertNull($userAsArray['nullable_datetime']);
    }

    public function test_casting_attributes()
    {
        $model = new TestModel1();
        $model->setDateTimeFormat('Y-m-d H:i:s');
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
        $this->assertEquals('1969-07-20', $arr['dateAttribute']);
        $this->assertEquals('1969-07-20 22:56:00', $arr['datetimeAttribute']);
        $this->assertEquals(-14173440, $arr['timestampAttribute']);
    }

    public function test_model_dates_can_be_set_from_carbon()
    {
        $user = TestModel1::create([
            'nullable_date' => null,
            'nullable_datetime' => null,
            'date' => Carbon::create(2017, 10, 10, 10, 8, 0),
            'datetime' => Carbon::create(2017, 10, 10, 10, 8, 0),
        ]);

        $this->assertInstanceOf(\DateTime::class, $user->date);
        $this->assertInstanceOf(\DateTime::class, $user->datetime);
        $this->assertNull($user->nullable_date);
        $this->assertNull($user->nullable_datetime);

        $userAsArray = $user->toArray();

        $this->assertSame('2017-10-10', $userAsArray['date']);
        $this->assertSame('2017-10-10 10:08:00', $userAsArray['datetime']);
        $this->assertNull($userAsArray['nullable_date']);
        $this->assertNull($userAsArray['nullable_datetime']);
    }

//    public function test_user_can_update_nullable_date()
//    {
//        $user = TestModel1::create([
//            'nullable_date' => null,
//        ]);
//
//        $user->fill([
//            'nullable_date' => $now = \Illuminate\Support\Carbon::now(),
//        ]);
//        $this->assertTrue($user->isDirty('nullable_date'));
//
//        $user->save();
//        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
//    }

    public function test_attribute_changes()
    {
        $user = TestModel2::create([
            'name' => str_random(), 'title' => str_random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $name = str_random();

        $this->assertEquals(['name' => $name], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->save();

        $this->assertEmpty($user->getDirty());
        $this->assertEquals(['name' => $name], $user->getChanges());
        $this->assertTrue($user->wasChanged());
        $this->assertTrue($user->wasChanged('name'));
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $casts = [
        'nullable_date' => 'date',
        'date' => 'date',
        'nullable_datetime' => 'datetime',
        'datetime' => 'datetime',
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

class TestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = ['id'];
}
