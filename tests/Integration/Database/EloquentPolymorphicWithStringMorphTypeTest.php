<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Assert;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;

#[WithMigration]
class EloquentPolymorphicWithStringMorphTypeTest extends DatabaseTestCase
{
    /** @inheritDoc */
    protected function afterRefreshingDatabase()
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->nullableStringableMorphs('owner');
            $table->string('provider');
        });

        $user = UserFactory::new()->create([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
        ]);

        DB::table('integrations')->insert([
            'owner_type' => EloquentPolymorphicWithStringMorphTypeTestUser::class,
            'owner_id' => $user->id,
            'provider' => 'dummy_provider',
        ]);
    }

    public function test_it_can_query_from_polymorphic_model()
    {
        $user = EloquentPolymorphicWithStringMorphTypeTestUser::first();

        $user->loadMissing('integrations');

        Assert::assertArraySubset([
            ['owner_type' => EloquentPolymorphicWithStringMorphTypeTestUser::class, 'owner_id' => $user->getKey(), 'provider' => 'dummy_provider'],
        ], EloquentPolymorphicWithStringMorphTypeTestIntegration::where('owner_id', $user->id)->where('owner_type', EloquentPolymorphicWithStringMorphTypeTestUser::class)->get()->toArray());
    }

    public function test_it_can_query_using_relationship()
    {
        $user = EloquentPolymorphicWithStringMorphTypeTestUser::first();

        Assert::assertArraySubset([
            ['owner_type' => EloquentPolymorphicWithStringMorphTypeTestUser::class, 'owner_id' => $user->getKey(), 'provider' => 'dummy_provider'],
        ], $user->integrations()->get()->toArray());
    }

    public function test_it_can_query_using_load_missing()
    {
        $user = EloquentPolymorphicWithStringMorphTypeTestUser::query()->where('email', 'taylor@laravel.com')->first();

        $user->loadMissing('integrations');

        Assert::assertArraySubset([
            'name' => 'Taylor Otwell',
            'integrations' => [
                ['owner_type' => EloquentPolymorphicWithStringMorphTypeTestUser::class, 'owner_id' => $user->getKey(), 'provider' => 'dummy_provider'],
            ],
        ], $user->toArray());
    }
}

class EloquentPolymorphicWithStringMorphTypeTestUser extends Authenticatable
{
    protected $fillable = ['*'];
    protected $table = 'users';

    public function integrations()
    {
        return $this->morphMany(EloquentPolymorphicWithStringMorphTypeTestIntegration::class, 'owner', morphKeyType: 'string');
    }
}

class EloquentPolymorphicWithStringMorphTypeTestIntegration extends Model
{
    protected $fillable = ['*'];
    protected $table = 'integrations';

    public function owner()
    {
        return $this->morphTo('owner');
    }
}
