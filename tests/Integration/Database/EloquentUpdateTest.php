<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentUpdateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('job')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('test_model3', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('wallet_1');
            $table->unsignedInteger('wallet_2');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function testBasicUpdate()
    {
        TestUpdateModel1::create([
            'name' => Str::random(),
            'title' => 'Ms.',
        ]);

        TestUpdateModel1::where('title', 'Ms.')->delete();

        $this->assertCount(0, TestUpdateModel1::all());
    }

    public function testUpdateWithLimitsAndOrders()
    {
        if ($this->driver === 'sqlsrv') {
            $this->markTestSkipped('The limit keyword is not supported on MSSQL.');
        }

        for ($i = 1; $i <= 10; $i++) {
            TestUpdateModel1::create();
        }

        TestUpdateModel1::latest('id')->limit(3)->update(['title' => 'Dr.']);

        $this->assertSame('Dr.', TestUpdateModel1::find(8)->title);
        $this->assertNotSame('Dr.', TestUpdateModel1::find(7)->title);
    }

    public function testUpdatedAtWithJoins()
    {
        TestUpdateModel1::create([
            'name' => 'Abdul',
            'title' => 'Mr.',
        ]);

        TestUpdateModel2::create([
            'name' => Str::random(),
        ]);

        TestUpdateModel2::join('test_model1', function ($join) {
            $join->on('test_model1.id', '=', 'test_model2.id')
                ->where('test_model1.title', '=', 'Mr.');
        })->update(['test_model2.name' => 'Abdul', 'job' => 'Engineer']);

        $record = TestUpdateModel2::find(1);

        $this->assertSame('Engineer: Abdul', $record->job.': '.$record->name);
    }

    public function testSoftDeleteWithJoins()
    {
        TestUpdateModel1::create([
            'name' => Str::random(),
            'title' => 'Mr.',
        ]);

        TestUpdateModel2::create([
            'name' => Str::random(),
        ]);

        TestUpdateModel2::join('test_model1', function ($join) {
            $join->on('test_model1.id', '=', 'test_model2.id')
                ->where('test_model1.title', '=', 'Mr.');
        })->delete();

        $this->assertCount(0, TestUpdateModel2::all());
    }

    public function testIncrement()
    {
        TestUpdateModel3::create([
            'wallet_1' => 0,
            'wallet_2' => 0,
        ]);

        TestUpdateModel3::create([
            'wallet_1' => 0,
            'wallet_2' => 0,
        ])->delete();

        TestUpdateModel3::increment('wallet_1');
        TestUpdateModel3::incrementEach([
            'wallet_1' => 10,
            'wallet_2' => -20
        ]);

        $models = TestUpdateModel3::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertEquals(1 + 10, $models[0]->wallet_1);
        $this->assertEquals(-20, $models[0]->wallet_2);
        $this->assertEquals(0, $models[1]->wallet_1);
        $this->assertEquals(0, $models[1]->wallet_2);

        $record = TestUpdateModel3::create([
            'wallet_1' => 50,
            'wallet_2' => 70,
        ]);
        $record->incrementEach([
            'wallet_1' => 20,
            'wallet_2' => -40,
        ]);

        $models = TestUpdateModel3::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertEquals(1 + 10, $models[0]->wallet_1);
        $this->assertEquals(-20, $models[0]->wallet_2);
        $this->assertEquals(0, $models[1]->wallet_1);
        $this->assertEquals(0, $models[1]->wallet_2);
        $this->assertEquals(50 + 20, $models[2]->wallet_1);
        $this->assertEquals(70 - 40, $models[2]->wallet_2);
    }

    public function testIncrementOrDecrementIgnoresGlobalScopes()
    {
        /** @var TestUpdateModel3 $deletedModel */
        $deletedModel = tap(TestUpdateModel3::create([
            'wallet_1' => 0,
            'wallet_2' => 0,
        ]), fn ($model) => $model->delete());

        $deletedModel->increment('wallet_1');
        $deletedModel->incrementEach(['wallet_1' => 1, 'wallet_2' => 1]);

        $this->assertEquals(1 + 1, $deletedModel->wallet_1);
        $this->assertEquals(1, $deletedModel->wallet_2);

        $deletedModel->fresh();
        $this->assertEquals(1 + 1, $deletedModel->wallet_1);
        $this->assertEquals(1, $deletedModel->wallet_2);

        $deletedModel->decrement('wallet_1');
        $deletedModel->decrementEach(['wallet_1' => 1, 'wallet_2' => 1]);

        $this->assertEquals(0, $deletedModel->fresh()->wallet_1);
        $this->assertEquals(0, $deletedModel->fresh()->wallet_2);
    }
}

class TestUpdateModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
}

class TestUpdateModel2 extends Model
{
    use SoftDeletes;

    public $table = 'test_model2';
    protected $fillable = ['name'];
}

class TestUpdateModel3 extends Model
{
    use SoftDeletes;

    public $table = 'test_model3';
    protected $fillable = ['wallet_1', 'wallet_2'];
    protected $casts = ['deleted_at' => 'datetime'];
}
