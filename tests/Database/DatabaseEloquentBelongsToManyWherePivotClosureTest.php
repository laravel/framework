<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWherePivotClosureTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema(): void
    {
        $this->schema()->create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
        });

        $this->schema()->create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('title');
        });

        $this->schema()->create('project_user', function (Blueprint $t) {
            $t->unsignedBigInteger('project_id');
            $t->unsignedBigInteger('user_id');
            $t->string('role')->default('member');
            $t->boolean('muted')->default(false);
        });
    }

    protected function tearDown(): void
    {
        $this->schema()->drop('project_user');
        $this->schema()->drop('projects');
        $this->schema()->drop('users');
    }

    public function testWherePivotWithClosureCallsPivotModelScope(): void
    {
        $project = WherePivotClosureProject::create(['title' => 'Project 1']);
        $active = WherePivotClosureUser::create(['name' => 'Active User']);
        $muted = WherePivotClosureUser::create(['name' => 'Muted User']);

        $project->subscribers()->attach($active->id, ['muted' => false, 'role' => 'admin']);
        $project->subscribers()->attach($muted->id, ['muted' => true, 'role' => 'member']);

        $results = $project->subscribers()->wherePivot(function ($query) {
            $query->active();
        })->get();

        $this->assertCount(1, $results);
        $this->assertEquals($active->id, $results->first()->id);
    }

    public function testOrWherePivotWithClosureCallsPivotModelScope(): void
    {
        $project = WherePivotClosureProject::create(['title' => 'Project 1']);
        $admin = WherePivotClosureUser::create(['name' => 'Admin']);
        $active = WherePivotClosureUser::create(['name' => 'Active']);
        $mutedMember = WherePivotClosureUser::create(['name' => 'Muted Member']);

        $project->subscribers()->attach($admin->id, ['muted' => true, 'role' => 'admin']);
        $project->subscribers()->attach($active->id, ['muted' => false, 'role' => 'member']);
        $project->subscribers()->attach($mutedMember->id, ['muted' => true, 'role' => 'member']);

        $results = $project->subscribers()
            ->wherePivot('role', 'admin')
            ->orWherePivot(function ($query) {
                $query->active();
            })
            ->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $admin->id));
        $this->assertTrue($results->contains('id', $active->id));
    }

    public function testWherePivotClosureWithMultipleScopeConditions(): void
    {
        $project = WherePivotClosureProject::create(['title' => 'Project 1']);
        $activeAdmin = WherePivotClosureUser::create(['name' => 'Active Admin']);
        $activeMember = WherePivotClosureUser::create(['name' => 'Active Member']);
        $mutedAdmin = WherePivotClosureUser::create(['name' => 'Muted Admin']);

        $project->subscribers()->attach($activeAdmin->id, ['muted' => false, 'role' => 'admin']);
        $project->subscribers()->attach($activeMember->id, ['muted' => false, 'role' => 'member']);
        $project->subscribers()->attach($mutedAdmin->id, ['muted' => true, 'role' => 'admin']);

        $results = $project->subscribers()->wherePivot(function ($query) {
            $query->active()->admins();
        })->get();

        $this->assertCount(1, $results);
        $this->assertEquals($activeAdmin->id, $results->first()->id);
    }

    public function testWherePivotClosureWithInlineWhere(): void
    {
        $project = WherePivotClosureProject::create(['title' => 'Project 1']);
        $user1 = WherePivotClosureUser::create(['name' => 'User 1']);
        $user2 = WherePivotClosureUser::create(['name' => 'User 2']);

        $project->subscribers()->attach($user1->id, ['muted' => false, 'role' => 'admin']);
        $project->subscribers()->attach($user2->id, ['muted' => false, 'role' => 'member']);

        $results = $project->subscribers()->wherePivot(function ($query) {
            $query->where('role', 'admin');
        })->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user1->id, $results->first()->id);
    }

    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

class WherePivotClosureProject extends Eloquent
{
    protected $table = 'projects';
    protected $guarded = [];
    public $timestamps = false;

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(WherePivotClosureUser::class, 'project_user', 'project_id', 'user_id')
            ->using(WherePivotClosureSubscription::class)
            ->withPivot(['role', 'muted']);
    }
}

class WherePivotClosureUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}

class WherePivotClosureSubscription extends Pivot
{
    protected $table = 'project_user';

    public function scopeActive($query)
    {
        return $query->where('muted', false);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }
}
