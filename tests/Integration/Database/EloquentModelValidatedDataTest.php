<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ValidatedInput;

/**
 * @group integration
 */
class EloquentModelValidatedDataTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
        });
    }

    public function testCreatingModelsWithValidatedData()
    {
        $input = new ValidatedInput(['name' => 'Mohamed']);

        $model = new EloquentModelValidatedDataTestModel($input);
        $this->assertEquals('Mohamed', $model->name);

        $model = EloquentModelValidatedDataTestModel::make($input);
        $this->assertEquals('Mohamed', $model->name);

        $model = EloquentModelValidatedDataTestModel::create($input);
        $this->assertEquals('Mohamed', $model->name);

        $model = EloquentModelValidatedDataTestModel::forceCreate($input);
        $this->assertEquals('Mohamed', $model->name);
    }

    public function testFirstOrNewWithValidatedData()
    {
        $input = new ValidatedInput(['name' => 'Mohamed']);
        $values = new ValidatedInput(['title' => 'Developer']);

        $model = EloquentModelValidatedDataTestModel::firstOrNew($input);
        $this->assertEquals('Mohamed', $model->name);

        $model = EloquentModelValidatedDataTestModel::firstOrNew($input, $values);
        $this->assertEquals('Mohamed', $model->name);
        $this->assertEquals('Developer', $model->title);

        $model = EloquentModelValidatedDataTestModel::firstOrNew([], $values);
        $this->assertEquals('Developer', $model->title);
    }

    public function testFirstOrNewAppliesGuardingWhenNonValidatedDataIsPassed()
    {
        $this->expectException(MassAssignmentException::class);
        $model = EloquentModelValidatedDataTestModel::firstOrNew(['name' => 'Mohamed']);
    }

    public function testFirstOrNewAppliesGuardingWhenSomeNonValidatedDataIsPassed()
    {
        $this->expectException(MassAssignmentException::class);
        $model = EloquentModelValidatedDataTestModel::firstOrNew(
            new ValidatedInput(['name' => 'Mohamed']),
            ['title' => 'Dev']
        );
    }

    public function testFirstOrCreateWithValidatedData()
    {
        $model = EloquentModelValidatedDataTestModel::firstOrCreate(new ValidatedInput(['name' => 'Mohamed']));
        $this->assertEquals('Mohamed', $model->name);

        $model = EloquentModelValidatedDataTestModel::firstOrCreate(new ValidatedInput(['name' => 'Zain']), new ValidatedInput(['title' => 'Developer']));
        $this->assertEquals('Zain', $model->name);
        $this->assertEquals('Developer', $model->title);
    }

    public function testUpdateOrCreateWithValidatedData()
    {
        $model = EloquentModelValidatedDataTestModel::updateOrCreate(new ValidatedInput(['name' => 'Mohamed']));
        $this->assertEquals('Mohamed', $model->name);

        $model = EloquentModelValidatedDataTestModel::updateOrCreate(new ValidatedInput(['name' => 'Zain']), new ValidatedInput(['title' => 'Developer']));
        $this->assertEquals('Zain', $model->name);
        $this->assertEquals('Developer', $model->title);
    }

    public function testAcceptingValidatedData()
    {
        $input = new ValidatedInput(['name' => 'Mohamed']);

        $model = new EloquentModelValidatedDataTestModel();
        $model->forceFill($input);
        $this->assertEquals('Mohamed', $model->name);

        $model = new EloquentModelValidatedDataTestModel();
        $model->forceFill($input);
        $model->save();
        $model->update(new ValidatedInput(['name' => 'Zain']));
        $this->assertEquals('Zain', $model->name);

        $model = new EloquentModelValidatedDataTestModel();
        $model->forceFill($input);
        $model->save();
        $model->updateQuietly(new ValidatedInput(['name' => 'Zain']));
        $this->assertEquals('Zain', $model->name);
    }
}

class EloquentModelValidatedDataTestModel extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = ['*'];
}
