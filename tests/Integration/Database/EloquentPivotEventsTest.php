<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @group integration
 */
class EloquentPivotEventsTest extends DatabaseTestCase
{
    protected function setUp(): void
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
            $table->string('role')->nullable();
        });
    }

    public function test_pivot_will_trigger_events_to_be_fired()
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
}

class PivotEventsTestCollaborator extends Pivot
{
    public $table = 'project_users';

    public static $eventsCalled = [];

    public static function boot()
    {
        parent::boot();

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
