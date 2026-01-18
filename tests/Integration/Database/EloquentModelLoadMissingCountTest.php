<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Model::class, 'loadMissingCount')]
class EloquentModelLoadMissingCountTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase(): void
    {
        Schema::create((new ModelLoadMissingCountTestUser)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
        });

        Schema::create((new ModelLoadMissingCountTestArticle)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->boolean('published')->default(false);
        });
    }

    public function test_model_load_missing_count(): void
    {
        // Arrange

        $user = ModelLoadMissingCountTestUser::create(['email' => 'john@doe.com']);
        $user->articles()->create(['title' => 'Article 1']);

        DB::enableQueryLog();

        // Act

        $user->loadMissingCount('articles');

        // Assert

        $this->assertEquals(1, $user->articles_count);
        $this->assertCount(1, DB::getQueryLog());

        // Act (again)

        $user->loadMissingCount('articles');

        // Assert

        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_model_load_missing_count_with_closure_and_alias(): void
    {
        // Arrange

        $user = ModelLoadMissingCountTestUser::create(['email' => 'john@doe.com']);
        $user->articles()->create(['title' => 'Article 1', 'published' => true]);
        $user->articles()->create(['title' => 'Article 2', 'published' => false]);

        DB::enableQueryLog();

        // Act

        $user->loadMissingCount(['articles as published_articles_count' => fn ($query) => $query->wherePublished()]);

        // Assert

        $this->assertEquals(1, $user->published_articles_count);
        $this->assertCount(1, DB::getQueryLog());

        // Act (again)

        $user->loadMissingCount(['articles as published_articles_count' => fn ($query) => $query->wherePublished()]);

        // Assert

        $this->assertCount(1, DB::getQueryLog());
    }
}

class ModelLoadMissingCountTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function articles(): HasMany
    {
        return $this->hasMany(ModelLoadMissingCountTestArticle::class, 'user_id');
    }
}

class ModelLoadMissingCountTestArticle extends Model
{
    protected $table = 'articles';

    protected $guarded = [];

    public $timestamps = false;

    public function scopeWherePublished($query)
    {
        return $query->where('published', true);
    }
}
