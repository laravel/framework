<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentPivotTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('collaborators', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('project_id');
            $table->text('permissions')->nullable();
        });

        Schema::create('contributors', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('project_id');
            $table->text('permissions')->nullable();
        });
    }

    public function testPivotConvenientHelperReturnExpectedResult()
    {
        $user = PivotTestUser::forceCreate(['email' => 'taylor@laravel.com']);
        $user2 = PivotTestUser::forceCreate(['email' => 'ralph@ralphschindler.com']);
        $project = PivotTestProject::forceCreate(['name' => 'Test Project']);

        $project->contributors()->attach($user);
        $project->collaborators()->attach($user2);

        tap($project->contributors->first()->pivot, function ($pivot) {
            $this->assertEquals(1, $pivot->getKey());
            $this->assertEquals(1, $pivot->getQueueableId());
            $this->assertSame('user_id', $pivot->getRelatedKey());
            $this->assertSame('project_id', $pivot->getForeignKey());
        });

        tap($project->collaborators->first()->pivot, function ($pivot) {
            $this->assertNull($pivot->getKey());
            $this->assertSame('project_id:1:user_id:2', $pivot->getQueueableId());
            $this->assertSame('user_id', $pivot->getRelatedKey());
            $this->assertSame('project_id', $pivot->getForeignKey());
        });
    }
}

class PivotTestUser extends Model
{
    public $table = 'users';
}

class PivotTestProject extends Model
{
    public $table = 'projects';

    public function collaborators()
    {
        return $this->belongsToMany(
            PivotTestUser::class, 'collaborators', 'project_id', 'user_id'
        )->withPivot('permissions')
        ->using(PivotTestCollaborator::class);
    }

    public function contributors()
    {
        return $this->belongsToMany(PivotTestUser::class, 'contributors', 'project_id', 'user_id')
            ->withPivot('id', 'permissions')
            ->using(PivotTestContributor::class);
    }
}

class PivotTestCollaborator extends Pivot
{
    public $table = 'collaborators';

    protected $casts = [
        'permissions' => 'json',
    ];
}

class PivotTestContributor extends Pivot
{
    public $table = 'contributors';

    public $incrementing = true;

    protected $casts = [
        'permissions' => 'json',
    ];
}
