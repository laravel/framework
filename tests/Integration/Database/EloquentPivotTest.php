<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\PivotTestProject;
use Illuminate\Tests\App\Models\Relationships\PivotTestUser;

class EloquentPivotTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
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

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('project_id');
            $table->string('status');
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

    public function testPivotValuesCanBeSetFromRelationDefinition()
    {
        $user = PivotTestUser::forceCreate(['email' => 'taylor@laravel.com']);
        $active = PivotTestProject::forceCreate(['name' => 'Active Project']);
        $inactive = PivotTestProject::forceCreate(['name' => 'Inactive Project']);

        $this->assertSame('active', $user->activeSubscriptions()->newPivot()->status);
        $this->assertSame('inactive', $user->inactiveSubscriptions()->newPivot()->status);

        $user->activeSubscriptions()->attach($active);
        $user->inactiveSubscriptions()->attach($inactive);

        $this->assertSame('active', $user->activeSubscriptions->first()->pivot->status);
        $this->assertSame('inactive', $user->inactiveSubscriptions->first()->pivot->status);
    }
}
