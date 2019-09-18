<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentPivotEventsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('role')->nullable();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->integer('tag_id');
            $table->morphs('taggable');
            $table->unsignedInteger('rank')->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        // clear event log between requests
        PivotEventsTestCollaborator::$eventsCalled = [];
    }

    public function testPivotWillTriggerEventsToBeFired()
    {
        $user = PivotEventsTestUser::forceCreate(['email' => 'taylor@laravel.com']);
        $user2 = PivotEventsTestUser::forceCreate(['email' => 'ralph@ralphschindler.com']);
        $project = PivotEventsTestProject::forceCreate(['name' => 'Test Project']);

        $project->collaborators()->attach($user);
        $this->assertEquals(['saving', 'creating', 'created', 'saved'], PivotEventsTestCollaborator::$eventsCalled);

        PivotEventsTestCollaborator::$eventsCalled = [];
        $project->collaborators()->sync([$user2->id]);
        $this->assertEquals(['deleting', 'deleted', 'saving', 'creating', 'created', 'saved'], PivotEventsTestCollaborator::$eventsCalled);

        PivotEventsTestCollaborator::$eventsCalled = [];
        $project->collaborators()->sync([$user->id => ['role' => 'owner'], $user2->id => ['role' => 'contributor']]);
        $this->assertEquals(['saving', 'creating', 'created', 'saved', 'saving', 'updating', 'updated', 'saved'], PivotEventsTestCollaborator::$eventsCalled);

        PivotEventsTestCollaborator::$eventsCalled = [];
        $project->collaborators()->detach($user);
        $this->assertEquals(['deleting', 'deleted'], PivotEventsTestCollaborator::$eventsCalled);
    }

    public function testPivotWithPivotCriteriaTriggerEventsToBeFiredOnCreateUpdateNoneOnDetach()
    {
        $user = PivotEventsTestUser::forceCreate(['email' => 'taylor@laravel.com']);
        $user2 = PivotEventsTestUser::forceCreate(['email' => 'ralph@ralphschindler.com']);
        $project = PivotEventsTestProject::forceCreate(['name' => 'Test Project']);

        $project->contributors()->sync([$user->id, $user2->id]);
        $this->assertEquals(['saving', 'creating', 'created', 'saved', 'saving', 'creating', 'created', 'saved'], PivotEventsTestCollaborator::$eventsCalled);

        PivotEventsTestCollaborator::$eventsCalled = [];
        $project->contributors()->detach($user->id);
        $this->assertEquals([], PivotEventsTestCollaborator::$eventsCalled);
    }

    public function testMorphPivotWillTriggerEventsToBeFired()
    {
        $project = PivotEventsTestProject::forceCreate(['name' => 'Test Project']);
        $tag1 = PivotEventsTestTag::forceCreate(['name' => 'Database']);
        $tag2 = PivotEventsTestTag::forceCreate(['name' => 'Filesystem']);

        $project->tags()->attach($tag1);
        $this->assertEquals(['saving', 'creating', 'created', 'saved'], PivotEventsTestTaggable::$eventsCalled);

        PivotEventsTestTaggable::$eventsCalled = [];
        $project->tags()->sync([$tag2->id]);
        $this->assertEquals(['deleting', 'deleted', 'saving', 'creating', 'created', 'saved'], PivotEventsTestTaggable::$eventsCalled);

        PivotEventsTestTaggable::$eventsCalled = [];
        $project->tags()->sync([$tag1->id => ['rank' => 1], $tag2->id => ['rank' => 2]]);
        $this->assertEquals(['saving', 'creating', 'created', 'saved', 'saving', 'updating', 'updated', 'saved'], PivotEventsTestTaggable::$eventsCalled);

        PivotEventsTestTaggable::$eventsCalled = [];
        $project->tags()->detach($tag1);
        $this->assertEquals(['deleting', 'deleted'], PivotEventsTestTaggable::$eventsCalled);
    }
}

class PivotEventsTestUser extends Model
{
    public $table = 'users';
}

class PivotEventsTestProject extends Model
{
    public $table = 'projects';

    public function collaborators()
    {
        return $this->belongsToMany(
            PivotEventsTestUser::class, 'project_users', 'project_id', 'user_id'
        )->using(PivotEventsTestCollaborator::class);
    }

    public function contributors()
    {
        return $this->belongsToMany(PivotEventsTestUser::class, 'project_users', 'project_id', 'user_id')
            ->using(PivotEventsTestCollaborator::class)
            ->wherePivot('role', 'contributor');
    }

    public function tags()
    {
        return $this->morphToMany(PivotEventsTestTag::class, 'taggable', 'taggables', null, 'tag_id')
            ->withPivot('rank')
            ->using(PivotEventsTestTaggable::class)
            ->withTimestamps();
    }
}

class PivotEventsTestTag extends Model
{
    public $table = 'tags';

    public function users()
    {
        return $this->morphedByMany(PivotEventsTestUser::class, 'taggable');
    }

    public function projects()
    {
        return $this->morphedByMany(PivotEventsTestProject::class, 'taggable');
    }
}

trait PivotEventsTestTracksEvents
{
    public static $eventsCalled = [];

    public static function bootPivotEventsTestTracksEvents()
    {
        static::creating(function ($model) {
            static::$eventsCalled[] = 'creating';
        });

        static::created(function ($model) {
            static::$eventsCalled[] = 'created';
        });

        static::updating(function ($model) {
            static::$eventsCalled[] = 'updating';
        });

        static::updated(function ($model) {
            static::$eventsCalled[] = 'updated';
        });

        static::saving(function ($model) {
            static::$eventsCalled[] = 'saving';
        });

        static::saved(function ($model) {
            static::$eventsCalled[] = 'saved';
        });

        static::deleting(function ($model) {
            static::$eventsCalled[] = 'deleting';
        });

        static::deleted(function ($model) {
            static::$eventsCalled[] = 'deleted';
        });
    }
}

class PivotEventsTestCollaborator extends Pivot
{
    use PivotEventsTestTracksEvents;

    public $table = 'project_users';
}

class PivotEventsTestTaggable extends MorphPivot
{
    use PivotEventsTestTracksEvents;

    public $table = 'taggables';
}
