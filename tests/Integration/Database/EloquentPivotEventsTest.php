<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentPivotEventsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // clear event log between requests
        PivotEventsTestCollaborator::$eventsCalled = [];
    }

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

        Schema::create('equipments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('equipmentables', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('equipmentable');
            $table->foreignId('equipment_id');
        });

        Schema::create('project_users', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('project_id');
            $table->text('permissions')->nullable();
            $table->string('role')->nullable();
        });
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

    public function testCustomPivotUpdateEventHasExistingAttributes()
    {
        $_SERVER['pivot_attributes'] = false;

        $user = PivotEventsTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $project = PivotEventsTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->attach($user, ['permissions' => ['foo', 'bar']]);

        $project->collaborators()->updateExistingPivot($user->id, ['role' => 'Lead Developer']);

        $this->assertEquals(
            [
                'user_id' => '1',
                'project_id' => '1',
                'permissions' => '["foo","bar"]',
                'role' => 'Lead Developer',
            ],
            $_SERVER['pivot_attributes']
        );
    }

    public function testCustomPivotUpdateEventHasDirtyCorrect()
    {
        $_SERVER['pivot_dirty_attributes'] = false;

        $user = PivotEventsTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $project = PivotEventsTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->attach($user, ['permissions' => ['foo', 'bar'], 'role' => 'Developer']);

        $project->collaborators()->updateExistingPivot($user->id, ['role' => 'Lead Developer']);

        $this->assertSame(['role' => 'Lead Developer'], $_SERVER['pivot_dirty_attributes']);
    }

    public function testCustomMorphPivotClassDetachAttributes()
    {
        $project = PivotEventsTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        PivotEventsTestModelEquipment::deleting(function ($model) use ($project) {
            $this->assertInstanceOf(PivotEventsTestProject::class, $model->equipmentable);
            $this->assertEquals($project->id, $model->equipmentable->id);
        });

        $equipment = PivotEventsTestEquipment::forceCreate([
            'name' => 'important-equipment',
        ]);

        $project->equipments()->save($equipment);
        $equipment->projects()->sync([]);
    }
}

class PivotEventsTestUser extends Model
{
    public $table = 'users';
}

class PivotEventsTestEquipment extends Model
{
    public $table = 'equipments';

    public function getForeignKey()
    {
        return 'equipment_id';
    }

    public function projects()
    {
        return $this->morphedByMany(PivotEventsTestProject::class, 'equipmentable')->using(PivotEventsTestModelEquipment::class);
    }
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

    public function equipments()
    {
        return $this->morphToMany(PivotEventsTestEquipment::class, 'equipmentable')->using(PivotEventsTestModelEquipment::class);
    }
}

class PivotEventsTestModelEquipment extends MorphPivot
{
    public $table = 'equipmentables';

    public function equipment()
    {
        return $this->belongsTo(PivotEventsTestEquipment::class);
    }

    public function equipmentable()
    {
        return $this->morphTo();
    }
}

class PivotEventsTestCollaborator extends Pivot
{
    public $table = 'project_users';

    public $timestamps = false;

    protected $casts = [
        'permissions' => 'json',
    ];

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
            $_SERVER['pivot_attributes'] = $model->getAttributes();
            $_SERVER['pivot_dirty_attributes'] = $model->getDirty();
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
