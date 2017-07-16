<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentCustomPivotCastTest extends TestCase
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

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });

        Schema::create('projects', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('project_users', function ($table) {
            $table->integer('user_id');
            $table->integer('project_id');
            $table->text('permissions');
        });
    }

    public function test_casts_are_respected_on_attach()
    {
        $user = CustomPivotCastTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $project = CustomPivotCastTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->attach($user, ['permissions' => ['foo' => 'bar']]);
        $project = $project->fresh();

        $this->assertEquals(['foo' => 'bar'], $project->collaborators[0]->pivot->permissions);
    }

    public function test_casts_are_respected_on_sync()
    {
        $user = CustomPivotCastTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $project = CustomPivotCastTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->sync([$user->id => ['permissions' => ['foo' => 'bar']]]);
        $project = $project->fresh();

        $this->assertEquals(['foo' => 'bar'], $project->collaborators[0]->pivot->permissions);
    }
}

class CustomPivotCastTestUser extends Model
{
    public $table = 'users';
    public $timestamps = false;
}

class CustomPivotCastTestProject extends Model
{
    public $table = 'projects';
    public $timestamps = false;

    public function collaborators()
    {
        return $this->belongsToMany(
            CustomPivotCastTestUser::class, 'project_users', 'project_id', 'user_id'
        )->using(CustomPivotCastTestCollaborator::class)->withPivot('permissions');
    }
}

class CustomPivotCastTestCollaborator extends \Illuminate\Database\Eloquent\Relations\Pivot
{
    protected $casts = [
        'permissions' => 'json',
    ];
}
