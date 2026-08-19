<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\PivotSerializationTestClass;
use Illuminate\Tests\App\Models\Relationships\PivotSerializationTestCollectionClass;
use Illuminate\Tests\App\Models\Relationships\PivotSerializationTestProject;
use Illuminate\Tests\App\Models\Relationships\PivotSerializationTestTag;
use Illuminate\Tests\App\Models\Relationships\PivotSerializationTestUser;

class EloquentPivotSerializationTest extends DatabaseTestCase
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

        Schema::create('project_users', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('project_id');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->integer('tag_id');
            $table->integer('taggable_id');
            $table->string('taggable_type');
        });
    }

    public function testPivotCanBeSerializedAndRestored()
    {
        $user = PivotSerializationTestUser::forceCreate(['email' => 'taylor@laravel.com']);
        $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);
        $project->collaborators()->attach($user);

        $project = $project->fresh();

        $class = new PivotSerializationTestClass($project->collaborators->first()->pivot);
        $class = unserialize(serialize($class));

        $this->assertEquals($project->collaborators->first()->pivot->user_id, $class->pivot->user_id);
        $this->assertEquals($project->collaborators->first()->pivot->project_id, $class->pivot->project_id);

        $class->pivot->save();
    }

    public function testMorphPivotCanBeSerializedAndRestored()
    {
        $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);
        $tag = PivotSerializationTestTag::forceCreate(['name' => 'Test Tag']);
        $project->tags()->attach($tag);

        $project = $project->fresh();

        $class = new PivotSerializationTestClass($project->tags->first()->pivot);
        $class = unserialize(serialize($class));

        $this->assertEquals($project->tags->first()->pivot->tag_id, $class->pivot->tag_id);
        $this->assertEquals($project->tags->first()->pivot->taggable_id, $class->pivot->taggable_id);
        $this->assertEquals($project->tags->first()->pivot->taggable_type, $class->pivot->taggable_type);

        $class->pivot->save();
    }

    public function testCollectionOfPivotsCanBeSerializedAndRestored()
    {
        $user = PivotSerializationTestUser::forceCreate(['email' => 'taylor@laravel.com']);
        $user2 = PivotSerializationTestUser::forceCreate(['email' => 'mohamed@laravel.com']);
        $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);

        $project->collaborators()->attach($user);
        $project->collaborators()->attach($user2);

        $project = $project->fresh();

        $class = new PivotSerializationTestCollectionClass(DatabaseCollection::make($project->collaborators->map->pivot));
        $class = unserialize(serialize($class));

        $this->assertEquals($project->collaborators[0]->pivot->user_id, $class->pivots[0]->user_id);
        $this->assertEquals($project->collaborators[1]->pivot->project_id, $class->pivots[1]->project_id);
    }

    public function testCollectionOfMorphPivotsCanBeSerializedAndRestored()
    {
        $tag = PivotSerializationTestTag::forceCreate(['name' => 'Test Tag 1']);
        $tag2 = PivotSerializationTestTag::forceCreate(['name' => 'Test Tag 2']);
        $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);

        $project->tags()->attach($tag);
        $project->tags()->attach($tag2);

        $project = $project->fresh();

        $class = new PivotSerializationTestCollectionClass(DatabaseCollection::make($project->tags->map->pivot));
        $class = unserialize(serialize($class));

        $this->assertEquals($project->tags[0]->pivot->tag_id, $class->pivots[0]->tag_id);
        $this->assertEquals($project->tags[0]->pivot->taggable_id, $class->pivots[0]->taggable_id);
        $this->assertEquals($project->tags[0]->pivot->taggable_type, $class->pivots[0]->taggable_type);

        $this->assertEquals($project->tags[1]->pivot->tag_id, $class->pivots[1]->tag_id);
        $this->assertEquals($project->tags[1]->pivot->taggable_id, $class->pivots[1]->taggable_id);
        $this->assertEquals($project->tags[1]->pivot->taggable_type, $class->pivots[1]->taggable_type);
    }
}
