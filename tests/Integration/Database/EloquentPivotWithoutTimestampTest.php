<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Tests\Integration\Database\EloquentPivotWithoutTimestamp as App;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithConfig('auth.providers.users.model', App\User::class)]
#[WithMigration]
class EloquentPivotWithoutTimestampTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        App\migrate();
    }

    public function testAttachingModelWithoutTimestamps()
    {
        $now = $this->freezeSecond();

        $user = App\User::factory()->create();
        $role = App\Role::factory()->create();

        $user->roles()->attach($role->getKey(), ['notes' => 'Laravel']);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->getKey(),
            'role_id' => $role->getKey(),
            'notes' => 'Laravel',
            'created_at' => $now,
        ]);
    }
}

namespace Illuminate\Tests\Integration\Database\EloquentPivotWithoutTimestamp;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Factories\UserFactory;

#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasFactory;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('notes')
            ->using(UserRole::class)
            ->withTimestamps(updatedAt: false);
    }
}

#[UseFactory(RoleFactory::class)]
class Role extends Model
{
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('notes')
            ->using(UserRole::class);
    }
}

class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}

class UserRole extends Pivot
{
    public $table = 'role_user';

    public function getUpdatedAtColumn()
    {
        return null;
    }
}

function migrate()
{
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('role_user', function (Blueprint $table) {
        $table->foreignId('user_id');
        $table->foreignId('role_id');
        $table->text('notes');
        $table->timestamp('created_at')->nullable();
    });
}
