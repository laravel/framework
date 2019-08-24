<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentCustomPivotCustomEventsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('role')->nullable();
        });

        Event::listen(CustomPivotEvent::class, function (CustomPivotEvent $customPivotEvent) {
            $_SERVER['pivot_attributes'] = $customPivotEvent->pivot->getAttributes();
            $_SERVER['pivot_dirty_attributes'] = $customPivotEvent->pivot->getDirty();
        });
    }

    public function testCustomPivotUpdateEventHasExistingAttributes()
    {
        $_SERVER['pivot_attributes'] = false;

        $user = CustomPivotCustomEventsTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $project = CustomPivotCustomEventsTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->attach($user, ['permissions' => ['foo', 'bar']]);

        $project->collaborators()->updateExistingPivot($user->id, ['role' => 'Lead Developer']);

        $this->assertSame(
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

        $user = CustomPivotCustomEventsTestUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $project = CustomPivotCustomEventsTestProject::forceCreate([
            'name' => 'Test Project',
        ]);

        $project->collaborators()->attach($user, ['permissions' => ['foo', 'bar'], 'role' => 'Developer']);

        $project->collaborators()->updateExistingPivot($user->id, ['role' => 'Lead Developer']);

        $this->assertSame(['role' => 'Lead Developer'], $_SERVER['pivot_dirty_attributes']);
    }
}

class CustomPivotCustomEventsTestUser extends Model
{
    public $table = 'users';
    public $timestamps = false;
}

class CustomPivotCustomEventsTestProject extends Model
{
    public $table = 'projects';
    public $timestamps = false;

    public function collaborators()
    {
        return $this->belongsToMany(
            CustomPivotCustomEventsTestUser::class, 'project_users', 'project_id', 'user_id'
        )->using(CustomPivotCustomEventsTestCollaborator::class)->withPivot('role', 'permissions');
    }
}

class CustomPivotCustomEventsTestCollaborator extends Pivot
{
    public $dispatchesEvents = ['updated' => CustomPivotEvent::class];
    protected $casts = [
        'permissions' => 'json',
    ];
}

class CustomPivotEvent
{
    public $pivot;

    public function __construct(CustomPivotCustomEventsTestCollaborator $pivot)
    {
        $this->pivot = $pivot;
    }
}
