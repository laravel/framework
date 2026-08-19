<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Tests\App\Models\Relationships\PivotWithoutTimestampRole;
use Illuminate\Tests\App\Models\Relationships\PivotWithoutTimestampUser;
use Illuminate\Tests\Integration\Database\EloquentPivotWithoutTimestampTest as App;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Concerns\WithFixtures;

#[WithConfig('auth.providers.users.model', PivotWithoutTimestampUser::class)]
#[WithMigration]
class EloquentPivotWithoutTimestampTest extends DatabaseTestCase
{
    use WithFixtures;

    protected function afterRefreshingDatabase()
    {
        App\migrate();
    }

    public function testAttachingModelWithoutTimestamps()
    {
        $now = $this->freezeSecond();

        $user = PivotWithoutTimestampUser::factory()->create();
        $role = PivotWithoutTimestampRole::factory()->create();

        $user->roles()->attach($role->getKey(), ['notes' => 'Laravel']);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->getKey(),
            'role_id' => $role->getKey(),
            'notes' => 'Laravel',
            'created_at' => $now,
        ]);
    }
}
