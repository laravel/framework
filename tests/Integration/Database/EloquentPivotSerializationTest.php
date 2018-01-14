<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @group integration
 */
class EloquentPivotSerializationTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('projects', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('project_users', function ($table) {
            $table->integer('user_id');
            $table->integer('project_id');
        });
    }


    public function test_pivot_can_be_serialized_and_restored()
    {
        $user = PivotSerializationTestUser::forceCreate(['email' => 'taylor@laravel.com']);
        $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);
        $project->collaborators()->attach($user);

        $project = $project->fresh();

        $class = new PivotSerializationTestClass($project->collaborators->first()->pivot);
        $class = unserialize(serialize($class));

        $this->assertEquals($project->collaborators->first()->pivot->user_id, $class->collaborator->user_id);
        $this->assertEquals($project->collaborators->first()->pivot->project_id, $class->collaborator->project_id);
    }
}


class PivotSerializationTestClass
{
    use SerializesModels;

    public $collaborator;

    public function __construct($collaborator)
    {
        $this->collaborator = $collaborator;
    }
}


class PivotSerializationTestUser extends Model
{
    public $table = 'users';
}


class PivotSerializationTestProject extends Model
{
    public $table = 'projects';

    public function collaborators()
    {
        return $this->belongsToMany(
            PivotSerializationTestUser::class, 'project_users', 'project_id', 'user_id'
        )->using(PivotSerializationTestCollaborator::class);
    }
}


class PivotSerializationTestCollaborator extends Pivot
{
    public $table = 'project_users';
}
