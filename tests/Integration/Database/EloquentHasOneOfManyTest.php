<?php

namespace Illuminate\Tests\Integration\Database\EloquentHasOneOfManyTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\HasOneOfManyUser;
use Illuminate\Tests\App\Models\Relationships\Login;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentHasOneOfManyTest extends DatabaseTestCase
{
    public $retrievedLogins;

    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function ($table) {
            $table->id();
        });

        Schema::create('logins', function ($table) {
            $table->id();
            $table->foreignId('user_id');
        });

        Schema::create('states', function ($table) {
            $table->increments('id');
            $table->string('state');
            $table->string('type');
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    public function testItOnlyEagerLoadsRequiredModels()
    {
        $this->retrievedLogins = 0;
        HasOneOfManyUser::getEventDispatcher()->listen('eloquent.retrieved:*', function ($event, $models) {
            foreach ($models as $model) {
                if (get_class($model) == Login::class) {
                    $this->retrievedLogins++;
                }
            }
        });

        $user = HasOneOfManyUser::create();
        $user->latest_login()->create();
        $user->latest_login()->create();
        $user = HasOneOfManyUser::create();
        $user->latest_login()->create();
        $user->latest_login()->create();

        HasOneOfManyUser::with('latest_login')->get();

        $this->assertSame(2, $this->retrievedLogins);
    }

    public function testItGetsCorrectResultUsingAtLeastTwoAggregatesDistinctFromId()
    {
        $user = HasOneOfManyUser::create();

        $latestState = $user->states()->create([
            'state' => 'state',
            'type' => 'type',
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-03',
        ]);

        $oldestState = $user->states()->create([
            'state' => 'state',
            'type' => 'type',
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-02',
        ]);

        $this->assertSame($user->oldest_updated_state->id, $oldestState->id);
        $this->assertSame($user->oldest_updated_oldest_created_state->id, $oldestState->id);

        $users = HasOneOfManyUser::with('latest_updated_state', 'latest_updated_latest_created_state')->get();

        $this->assertSame($users[0]->latest_updated_state->id, $latestState->id);
        $this->assertSame($users[0]->latest_updated_latest_created_state->id, $latestState->id);
    }
}
