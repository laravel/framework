<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentStringKeyIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;
        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $db->bootEloquent();
        $db->setAsGlobal();

        DB::schema()->create('string_keys', function ($table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });

        DB::schema()->create('integer_keys', function ($table) {
            $table->integer('id')->primary();
            $table->softDeletes();
        });

        DB::table('integer_keys')->insert([['id' => 10], ['id' => 20]]);
        DB::table('string_keys')->insert([['id' => '10'], ['id' => '20']]);
        DB::connection()->enableQueryLog();
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();
        Model::clearBootedModels();
    }

    public function testDestroyPreservesIntegerKeyBindings()
    {
        $this->assertSame(0, IntegerKeyModel::destroy([10.5]));
        $this->assertSame([10.5], DB::getQueryLog()[0]['bindings']);
        $this->assertSame(1, IntegerKeyModel::destroy([10]));
        $this->assertSame([20], IntegerKeyModel::all()->modelKeys());
    }

    public function testForceDestroyPreservesIntegerKeyBindings()
    {
        $this->assertSame(0, SoftDeletingIntegerKeyModel::forceDestroy([10.5]));
        $this->assertSame([10.5], DB::getQueryLog()[0]['bindings']);
        $this->assertSame(1, SoftDeletingIntegerKeyModel::forceDestroy([10]));
        $this->assertSame([20], SoftDeletingIntegerKeyModel::withTrashed()->get()->modelKeys());
    }

    public function testFindManyUsesStringBindings()
    {
        $models = StringKeyModel::findMany(collect([10]));

        $this->assertSame(['10'], $models->modelKeys());
        $this->assertSame(['10'], DB::getQueryLog()[0]['bindings']);
    }

    public function testDestroyUsesStringBindings()
    {
        $this->assertSame(1, StringKeyModel::destroy([10]));
        $this->assertSame(['10'], DB::getQueryLog()[0]['bindings']);
        $this->assertSame(['20'], StringKeyModel::all()->modelKeys());
    }

    public function testSoftDestroyUsesStringBindings()
    {
        $this->assertSame(1, SoftDeletingStringKeyModel::destroy(collect([10])));
        $this->assertSame(['10'], DB::getQueryLog()[0]['bindings']);
        $this->assertSame(['20'], SoftDeletingStringKeyModel::all()->modelKeys());
        $this->assertSame(['10'], SoftDeletingStringKeyModel::onlyTrashed()->get()->modelKeys());
    }

    public function testForceDestroyUsesStringBindings()
    {
        SoftDeletingStringKeyModel::find('10')->delete();
        DB::flushQueryLog();

        $this->assertSame(1, SoftDeletingStringKeyModel::forceDestroy([10]));
        $this->assertSame(['10'], DB::getQueryLog()[0]['bindings']);
        $this->assertSame(['20'], SoftDeletingStringKeyModel::withTrashed()->get()->modelKeys());
    }
}

class StringKeyModel extends Model
{
    protected $table = 'string_keys';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}

class SoftDeletingStringKeyModel extends StringKeyModel
{
    use SoftDeletes;
}

class IntegerKeyModel extends StringKeyModel
{
    protected $table = 'integer_keys';

    protected $keyType = 'int';
}

class SoftDeletingIntegerKeyModel extends IntegerKeyModel
{
    use SoftDeletes;
}
