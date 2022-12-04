<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\CalculableExpression;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentUpdateTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SERVER['__test.saving.attributes'],
            $_SERVER['__test.updating.attributes'],
            $_SERVER['__test.saved.attributes'],
            $_SERVER['__test.updated.attributes'],
        );
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
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
            $table->unsignedInteger('counter');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('test_model4', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('counter');
            $table->unsignedInteger('bonus');
            $table->decimal('decimal_counter', 8, 4)->default(0);
            $table->decimal('decimal_bonus', 8, 4)->default(0);
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
            'counter' => 0,
        ]);

        TestUpdateModel3::create([
            'counter' => 0,
        ])->delete();

        TestUpdateModel3::increment('counter');

        $models = TestUpdateModel3::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertEquals(1, $models[0]->counter);
        $this->assertEquals(0, $models[1]->counter);
    }

    public function testRawExpression()
    {
        $model = TestUpdateModel4::create([
            'counter' => 10,
            'bonus' => 3,
        ]);

        $model->update([
            'counter' => DB::raw('counter + 2'),
        ]);

        $this->assertInstanceOf(Expression::class, $model->counter);

        $model->refresh();

        $this->assertEquals(12, $model->counter);
    }

    /**
     * @dataProvider calculateExpressionDataProvider
     */
    public function testCalculableExpression($column, $expression, $expected)
    {
        /** @var Model $model */
        $model = TestUpdateModel4::create([
            'counter' => 5,
            'bonus' => 3,
            'decimal_bonus' => 10,
            'decimal_counter' => 5.6,
        ]);

        $model->update([
            $column => DB::calculate(
                collect($expression)->map(fn ($item) => is_callable($item) ? $this->wrap($item()) : $item)->join('')
            ),
        ]);

        $this->assertNotInstanceOf(CalculableExpression::class, $model->$column);

        $this->assertEquals($expected, $model->$column);

        // Check again after retrieving the model from the database:
        $model->refresh();
        $this->assertEquals($expected, $model->$column);
    }

    public static function calculateExpressionDataProvider()
    {
        // This is a function wrapper that returns its own value, which we apply to SQL keywords
        // so that we can wrap them using the correct grammar in the test.
        $w = fn($value) => fn () => $value;

        return [
            // Format: 'Explanation' => ['column', ['expressionPart', 'expressionPart', 'expressionPart'], 'expectedResult']
            'With quotes simple integer' => ['counter', [$w('counter'), ' + 25'], 30],
            'With quotes complex integers' => ['counter', ['(', $w('counter'), ' + ', $w('bonus'), ') * 25'], 200],
            'With quotes even more complex integers' => ['counter', ['(2 * ', $w('counter'), ' + (', $w('counter'), ' + ', $w('bonus'), ') * 25)'], 210],
            'With quotes even more complex integers and decimals' => ['decimal_counter', ['(2 * ', $w('decimal_counter'), ' + (', $w('decimal_counter'), ' + ', $w('bonus'), ') * 25)'], 226.2],
            'Without quotes simple integer multiplication' => ['counter', 'counter * 2', 10],
            'Without quotes simple integer addition' => ['counter', 'counter + 2', 7],
            'Without quotes simple integer subtraction' => ['counter', 'counter - 2', 3],
            'Without quotes simple integer division' => ['counter', 'counter / 4', 1],
            'Without quotes simple decimal multiplication' => ['decimal_bonus', 'decimal_bonus * 2.45', 24.5],
            'Without quotes simple decimal addition' => ['decimal_bonus', 'decimal_bonus + 2.45', 12.45],
            'Without quotes simple decimal subtraction' => ['decimal_bonus', 'decimal_bonus - 2.45', 7.55],
            'Without quotes simple decimal division' => ['decimal_bonus', 'decimal_bonus / 1.6', 6.25],
            'Mixed quotes complex decimals' => ['decimal_bonus', ['(', $w('decimal_bonus'), ' * 1.2) + (', $w('decimal_counter'), ' / 4)'], 13.4],
            'Mixed quotes complex integers and decimals' => ['decimal_bonus', ['(', $w('counter'), ' + (', $w('decimal_bonus'), ' * 1.05))'], 15.5],
            'Mixed quotes complex' => ['decimal_counter', ['(counter * ', $w('decimal_counter'), ') + (bonus % 3)'], 28],
        ];
    }

    public function testInvalidCalculableExpressionOnNotExistingModel()
    {
        $this->expectException(\LogicException::class);

        $model = new TestUpdateModel4([
            'counter' => 4,
            'bonus' => 3,
        ]);

        $model->counter = DB::calculate($this->wrap('counter').' + '.$this->wrap('bonus').' * 2');

        $model->save();
    }

    public function testValidCalculableExpressionOnNotExistingModel()
    {
        $model = new TestUpdateModel4([
            'counter' => 4,
            'bonus' => 3,
        ]);

        $model->counter = DB::calculate('5 * 2');

        $model->save();

        $this->assertEquals(10, $model->counter);
        $this->assertEquals(3, $model->bonus);
    }

    public function testCalculablePropertyRemainsAsExpressionWhileUnsaved()
    {
        /** @var Model $model */
        $model = TestUpdateModel4::create([
            'counter' => 5,
            'bonus' => 3,
            'decimal_bonus' => 10,
            'decimal_counter' => 5.6,
        ]);

        $model->counter = DB::calculate(static::wrap('counter').' * ('.static::wrap('bonus').' + 2)');

        $this->assertInstanceOf(CalculableExpression::class, $model->counter);

        $model->save();

        // During the saving/updating events, the attribute will still be an Expression instance.
        $this->assertInstanceof(CalculableExpression::class, $_SERVER['__test.saving.attributes']['counter']);
        $this->assertInstanceof(CalculableExpression::class, $_SERVER['__test.updating.attributes']['counter']);

        // During saved/updated, the attribute should have been resolved to the calculable value.
        $this->assertEquals(25, $_SERVER['__test.saved.attributes']['counter']);
        $this->assertEquals(25, $_SERVER['__test.updated.attributes']['counter']);
        $this->assertEquals(25, $model->counter);
    }

    /**
     * Convenience function to wrap a query string value into an identifier.
     * @param  mixed  $value
     * @return string
     */
    protected function wrap($value)
    {
        return DB::connection()->getQueryGrammar()->wrap($value);
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
    protected $fillable = ['counter'];
    protected $casts = ['deleted_at' => 'datetime'];
}

class TestUpdateModel4 extends Model
{
    public $table = 'test_model4';
    protected $fillable = ['counter', 'bonus', 'decimal_counter', 'decimal_bonus'];
    protected $casts = [
        'counter' => 'integer',
        'bonus' => 'integer',
        'decimal_counter' => 'float',
        'decimal_bonus' => 'float',
    ];

    public static function boot()
    {
        parent::boot();

        static::updating(function (TestUpdateModel4 $model) {
            $_SERVER['__test.updating.attributes'] = $model->attributes;
        });

        static::updated(function (TestUpdateModel4 $model) {
            $_SERVER['__test.updated.attributes'] = $model->attributes;
        });

        static::saving(function (TestUpdateModel4 $model) {
            $_SERVER['__test.saving.attributes'] = $model->attributes;
        });

        static::saved(function ($model) {
            $_SERVER['__test.saved.attributes'] = $model->attributes;
        });
    }
}
