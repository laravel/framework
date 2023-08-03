<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use stdClass;

class EloquentCastTest extends MySqlTestCase
{
    protected $driver = 'mysql';

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->integer('created_at');
            $table->integer('updated_at');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('users');
    }

    public function testItCastTimestampsCreatedByTheBuilderWhenTimeHasNotPassed()
    {
        Carbon::setTestNow(now());

        $createdAt = now()->timestamp;

        $user = UserWithIntTimestamps::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $user->created_at->timestamp);
        $this->assertSame($createdAt, $user->updated_at->timestamp);

        $user->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $user->created_at->timestamp);
        $this->assertSame($createdAt, $user->updated_at->timestamp);
        $this->assertSame($createdAt, $user->fresh()->updated_at->timestamp);
    }

    public function testItCastTimestampsCreatedByTheBuilderWhenTimeHasPassed()
    {
        Carbon::setTestNow(now());

        $createdAt = now()->timestamp;

        $user = UserWithIntTimestamps::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $user->created_at->timestamp);
        $this->assertSame($createdAt, $user->updated_at->timestamp);

        Carbon::setTestNow(now()->addSecond());

        $updatedAt = now()->timestamp;

        $user->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $user->created_at->timestamp);
        $this->assertSame($updatedAt, $user->updated_at->timestamp);
        $this->assertSame($updatedAt, $user->fresh()->updated_at->timestamp);
    }
}

class UserWithIntTimestamps extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    protected $casts = [
        'created_at' => UnixTimeStampToCarbon::class,
        'updated_at' => UnixTimeStampToCarbon::class,
    ];
}

class UnixTimeStampToCarbon implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return now();
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return 500;
    }
}
