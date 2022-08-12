<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentCustomPivotCastTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('project_users', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('project_id');
            $table->text('permissions');
        });
    }

    public function testCastsAreRespectedOnAttach()
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

    public function testCastsAreRespectedOnAttachArray()
    {
        $user = CustomPivotCastTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user2 = CustomPivotCastTestUser::forceCreate([
            'email' => 'mohamed@laravel.com',
        ]);

        $project = CustomPivotCastTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->attach([
            $user->id => ['permissions' => ['foo' => 'bar']],
            $user2->id => ['permissions' => ['baz' => 'bar']],
        ]);
        $project = $project->fresh();

        $this->assertEquals(['foo' => 'bar'], $project->collaborators[0]->pivot->permissions);
        $this->assertEquals(['baz' => 'bar'], $project->collaborators[1]->pivot->permissions);
    }

    public function testCastsAreRespectedOnSync()
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

    public function testCastsAreRespectedOnSyncArray()
    {
        $user = CustomPivotCastTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user2 = CustomPivotCastTestUser::forceCreate([
            'email' => 'mohamed@laravel.com',
        ]);

        $project = CustomPivotCastTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->sync([
            $user->id => ['permissions' => ['foo' => 'bar']],
            $user2->id => ['permissions' => ['baz' => 'bar']],
        ]);
        $project = $project->fresh();

        $this->assertEquals(['foo' => 'bar'], $project->collaborators[0]->pivot->permissions);
        $this->assertEquals(['baz' => 'bar'], $project->collaborators[1]->pivot->permissions);
    }

    public function testCastsAreRespectedOnSyncArrayWhileUpdatingExisting()
    {
        $user = CustomPivotCastTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user2 = CustomPivotCastTestUser::forceCreate([
            'email' => 'mohamed@laravel.com',
        ]);

        $project = CustomPivotCastTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->attach([
            $user->id => ['permissions' => ['foo' => 'bar']],
            $user2->id => ['permissions' => ['baz' => 'bar']],
        ]);

        $project->collaborators()->sync([
            $user->id => ['permissions' => ['foo1' => 'bar1']],
            $user2->id => ['permissions' => ['baz2' => 'bar2']],
        ]);

        $project = $project->fresh();

        $this->assertEquals(['foo1' => 'bar1'], $project->collaborators[0]->pivot->permissions);
        $this->assertEquals(['baz2' => 'bar2'], $project->collaborators[1]->pivot->permissions);
    }

    public function testDefaultAttributesAreRespectedAndCastsAreRespected()
    {
        $project = CustomPivotCastTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $pivot = $project->collaborators()->newPivot();

        $this->assertEquals(['permissions' => ['create', 'update']], $pivot->toArray());
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

class CustomPivotCastTestCollaborator extends Pivot
{
    protected $attributes = [
        'permissions' => '["create", "update"]',
    ];

    protected $casts = [
        'permissions' => 'json',
    ];
}
