<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Model::class, 'loadMissingAggregate')]
class EloquentModelLoadMissingAggregateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase(): void
    {
        Schema::create((new ModelLoadMissingAggregateTestUser)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
        });

        Schema::create((new ModelLoadMissingAggregateTestArticle)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->integer('views')->default(0);
        });
    }

    public function test_load_missing_aggregate(): void
    {
        // Arrange

        $user = ModelLoadMissingAggregateTestUser::create(['email' => 'john@doe.com']);

        $user->articles()->createMany([
            ['title' => 'Article 1', 'views' => 10],
            ['title' => 'Article 2', 'views' => 20],
        ]);

        // Act

        $user->loadMissingAggregate('articles', 'views', 'sum');

        // Assert

        $this->assertEquals(30, $user->articles_sum_views);
    }

    public function test_does_not_reload_existing_aggregate(): void
    {
        // Arrange

        $user = ModelLoadMissingAggregateTestUser::create(['email' => 'john@doe.com']);

        $user->articles()->createMany([
            ['title' => 'Article 1', 'views' => 10],
            ['title' => 'Article 2', 'views' => 20],
        ]);

        $user->setAttribute('articles_sum_views', 100);

        // Act

        $user->loadMissingAggregate('articles', 'views', 'sum');

        // Assert

        $this->assertEquals(100, $user->articles_sum_views);
    }
}

class ModelLoadMissingAggregateTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function articles(): HasMany
    {
        return $this->hasMany(ModelLoadMissingAggregateTestArticle::class, 'user_id');
    }
}

class ModelLoadMissingAggregateTestArticle extends Model
{
    protected $table = 'articles';

    protected $guarded = [];

    public $timestamps = false;
}
