<?php

namespace Illuminate\Tests\Integration\Database\MariaDb;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class EloquentCastTest extends MariaDbTestCase
{
    protected $driver = 'mariadb';

    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->integer('created_at');
            $table->integer('updated_at');
        });

        Schema::create('users_nullable_timestamps', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
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

        $castUser = UserWithIntTimestampsViaCasts::create([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser = UserWithIntTimestampsViaAttribute::create([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser = UserWithIntTimestampsViaMutator::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->timestamp);
        $this->assertSame($createdAt, $castUser->updated_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->created_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->updated_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->created_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->updated_at->timestamp);

        $castUser->update([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser->update([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->timestamp);
        $this->assertSame($createdAt, $castUser->updated_at->timestamp);
        $this->assertSame($createdAt, $castUser->fresh()->updated_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->created_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->updated_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->fresh()->updated_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->created_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->updated_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->fresh()->updated_at->timestamp);
    }

    public function testItCastTimestampsCreatedByTheBuilderWhenTimeHasPassed()
    {
        Carbon::setTestNow(now());
        $createdAt = now()->timestamp;

        $castUser = UserWithIntTimestampsViaCasts::create([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser = UserWithIntTimestampsViaAttribute::create([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser = UserWithIntTimestampsViaMutator::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->timestamp);
        $this->assertSame($createdAt, $castUser->updated_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->created_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->updated_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->created_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->updated_at->timestamp);

        Carbon::setTestNow(now()->addSecond());
        $updatedAt = now()->timestamp;

        $castUser->update([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser->update([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->timestamp);
        $this->assertSame($updatedAt, $castUser->updated_at->timestamp);
        $this->assertSame($updatedAt, $castUser->fresh()->updated_at->timestamp);
        $this->assertSame($createdAt, $attributeUser->created_at->timestamp);
        $this->assertSame($updatedAt, $attributeUser->updated_at->timestamp);
        $this->assertSame($updatedAt, $attributeUser->fresh()->updated_at->timestamp);
        $this->assertSame($createdAt, $mutatorUser->created_at->timestamp);
        $this->assertSame($updatedAt, $mutatorUser->updated_at->timestamp);
        $this->assertSame($updatedAt, $mutatorUser->fresh()->updated_at->timestamp);
    }

    public function testItCastTimestampsUpdatedByAMutator()
    {
        Carbon::setTestNow(now());

        $mutatorUser = UserWithUpdatedAtViaMutator::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertNull($mutatorUser->updated_at);

        Carbon::setTestNow(now()->addSecond());
        $updatedAt = now()->timestamp;

        $mutatorUser->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($updatedAt, $mutatorUser->updated_at->timestamp);
        $this->assertSame($updatedAt, $mutatorUser->fresh()->updated_at->timestamp);
    }
}

class UserWithIntTimestampsViaCasts extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected $casts = [
        'created_at' => UnixTimeStampToCarbon::class,
        'updated_at' => UnixTimeStampToCarbon::class,
    ];
}

class UnixTimeStampToCarbon implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return Carbon::parse($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return Carbon::parse($value)->timestamp;
    }
}

class UserWithIntTimestampsViaAttribute extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->timestamp,
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->timestamp,
        );
    }
}

class UserWithIntTimestampsViaMutator extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value);
    }

    protected function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->timestamp;
    }

    protected function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value);
    }

    protected function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->timestamp;
    }
}

class UserWithUpdatedAtViaMutator extends Model
{
    protected $table = 'users_nullable_timestamps';

    protected $fillable = ['email', 'updated_at'];

    public function setUpdatedAtAttribute($value)
    {
        if (! $this->id) {
            return;
        }

        $this->updated_at = $value;
    }
}
