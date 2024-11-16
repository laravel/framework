<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelInfoExtractor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModelInfoExtractorTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('parent_test_model', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid();
            $table->string('name');
            $table->boolean('a_bool');
            $table->foreignId('parent_model_id')->constrained();
            $table->timestamp('nullable_date')->nullable();
            $table->timestamps();
        });
    }

    public function test_extracts_model_data()
    {
        $extractor = new ModelInfoExtractor($this->app);
        $modelInfo = $extractor->handle(TestModel1::class);

        $this->assertEquals(TestModel1::class, $modelInfo['class']);
        $this->assertEquals('testing', $modelInfo['database']);
        $this->assertEquals('test_model1', $modelInfo['table']);
        $this->assertNull($modelInfo['policy']);
        $this->assertCount(8, $modelInfo['attributes']);

        $this->assertEqualsCanonicalizing([
            "name" => "id",
            "type" => "integer",
            "increments" => true,
            "nullable" => false,
            "default" => null,
            "unique" => true,
            "fillable" => true,
            "hidden" => false,
            "appended" => null,
            "cast" => null,
        ], $modelInfo['attributes'][0]);

        $this->assertEqualsCanonicalizing([
            "name" => "uuid",
            "type" => "varchar",
            "increments" => false,
            "nullable" => false,
            "default" => null,
            "unique" => false,
            "fillable" => true,
            "hidden" => false,
            "appended" => null,
            "cast" => null,
        ], $modelInfo['attributes'][1]);

        $this->assertEqualsCanonicalizing([
            "name" => "name",
            "type" => "varchar",
            "increments" => false,
            "nullable" => false,
            "default" => null,
            "unique" => false,
            "fillable" => false,
            "hidden" => false,
            "appended" => null,
            "cast" => null
        ], $modelInfo['attributes'][2]);

        $this->assertEqualsCanonicalizing([
            "name" => "a_bool",
            "type" => "tinyint(1)",
            "increments" => false,
            "nullable" => false,
            "default" => null,
            "unique" => false,
            "fillable" => true,
            "hidden" => false,
            "appended" => null,
            "cast" => "bool",
        ], $modelInfo['attributes'][3]);

        $this->assertEqualsCanonicalizing([
            "name" => "parent_model_id",
            "type" => "integer",
            "increments" => false,
            "nullable" => false,
            "default" => null,
            "unique" => false,
            "fillable" => true,
            "hidden" => false,
            "appended" => null,
            "cast" => null,
        ], $modelInfo['attributes'][4]);

        $this->assertEqualsCanonicalizing([
            "name" => "nullable_date",
            "type" => "datetime",
            "increments" => false,
            "nullable" => true,
            "default" => null,
            "unique" => false,
            "fillable" => true,
            "hidden" => false,
            "appended" => null,
            "cast" => "datetime",
        ], $modelInfo['attributes'][5]);

        $this->assertEqualsCanonicalizing([
            "name" => "created_at",
            "type" => "datetime",
            "increments" => false,
            "nullable" => true,
            "default" => null,
            "unique" => false,
            "fillable" => true,
            "hidden" => false,
            "appended" => null,
            "cast" => "datetime",
        ], $modelInfo['attributes'][6]);

        $this->assertEqualsCanonicalizing([
            "name" => "updated_at",
            "type" => "datetime",
            "increments" => false,
            "nullable" => true,
            "default" => null,
            "unique" => false,
            "fillable" => true,
            "hidden" => false,
            "appended" => null,
            "cast" => "datetime",
        ], $modelInfo['attributes'][7]);

        $this->assertCount(1, $modelInfo['relations']);
        $this->assertEqualsCanonicalizing([
            "name" => "parentModel",
            "type" => "BelongsTo",
            "related" => "Illuminate\Tests\Integration\Database\ParentTestModel",
        ], $modelInfo['relations'][0]);

        $this->assertEmpty($modelInfo['events']);
        $this->assertCount(1, $modelInfo['observers']);
        $this->assertEquals('created', $modelInfo['observers'][0]['event']);
        $this->assertCount(1, $modelInfo['observers'][0]['observer']);
        $this->assertEquals("Illuminate\Tests\Integration\Database\TestModel1Observer@created", $modelInfo['observers'][0]['observer'][0]);
    }
}


#[ObservedBy(TestModel1Observer::class)]
class TestModel1 extends Model
{
    use HasUuids;

    public $table = 'test_model1';
    protected $guarded = ['name'];
    protected $casts = ['nullable_date' => 'datetime', 'a_bool' => 'bool'];

    public function parentModel(): BelongsTo
    {
        return $this->belongsTo(ParentTestModel::class);
    }
}

class ParentTestModel extends Model
{
    public $table = 'parent_model';
    public $timestamps = false;
}

class TestModel1Observer
{
    public function created()
    {
    }
}
