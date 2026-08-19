<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Casts\UserWithIntTimestampsViaAttribute;
use Illuminate\Tests\App\Models\Casts\UserWithIntTimestampsViaCasts;
use Illuminate\Tests\App\Models\Casts\UserWithIntTimestampsViaMutator;
use Illuminate\Tests\App\Models\Casts\UserWithUpdatedAtViaMutator;

class EloquentCastTest extends MySqlTestCase
{
    protected $driver = 'mysql';

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
        Carbon::setTestNow($now = Carbon::now());
        $createdAt = $now->getTimestamp();

        $castUser = UserWithIntTimestampsViaCasts::create([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser = UserWithIntTimestampsViaAttribute::create([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser = UserWithIntTimestampsViaMutator::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $castUser->updated_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->updated_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->updated_at->getTimestamp());

        $castUser->update([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser->update([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $castUser->updated_at->getTimestamp());
        $this->assertSame($createdAt, $castUser->fresh()->updated_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->updated_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->fresh()->updated_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->updated_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->fresh()->updated_at->getTimestamp());
    }

    public function testItCastTimestampsCreatedByTheBuilderWhenTimeHasPassed()
    {
        Carbon::setTestNow($now = Carbon::now());
        $createdAt = $now->getTimestamp();

        $castUser = UserWithIntTimestampsViaCasts::create([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser = UserWithIntTimestampsViaAttribute::create([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser = UserWithIntTimestampsViaMutator::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $castUser->updated_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->updated_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->created_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->updated_at->getTimestamp());

        Carbon::setTestNow($now->addSecond());
        $updatedAt = $now->getTimestamp();

        $castUser->update([
            'email' => fake()->unique()->email,
        ]);
        $attributeUser->update([
            'email' => fake()->unique()->email,
        ]);
        $mutatorUser->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($createdAt, $castUser->created_at->getTimestamp());
        $this->assertSame($updatedAt, $castUser->updated_at->getTimestamp());
        $this->assertSame($updatedAt, $castUser->fresh()->updated_at->getTimestamp());
        $this->assertSame($createdAt, $attributeUser->created_at->getTimestamp());
        $this->assertSame($updatedAt, $attributeUser->updated_at->getTimestamp());
        $this->assertSame($updatedAt, $attributeUser->fresh()->updated_at->getTimestamp());
        $this->assertSame($createdAt, $mutatorUser->created_at->getTimestamp());
        $this->assertSame($updatedAt, $mutatorUser->updated_at->getTimestamp());
        $this->assertSame($updatedAt, $mutatorUser->fresh()->updated_at->getTimestamp());
    }

    public function testItCastTimestampsUpdatedByAMutator()
    {
        Carbon::setTestNow($now = Carbon::now());

        $mutatorUser = UserWithUpdatedAtViaMutator::create([
            'email' => fake()->unique()->email,
        ]);

        $this->assertNull($mutatorUser->updated_at);

        Carbon::setTestNow($now->addSecond());
        $updatedAt = $now->getTimestamp();

        $mutatorUser->update([
            'email' => fake()->unique()->email,
        ]);

        $this->assertSame($updatedAt, $mutatorUser->updated_at->getTimestamp());
        $this->assertSame($updatedAt, $mutatorUser->fresh()->updated_at->getTimestamp());
    }
}
