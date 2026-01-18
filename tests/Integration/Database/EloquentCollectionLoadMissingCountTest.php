<?php

namespace Illuminate\Tests\Integration\Database;

use Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversMethod(Collection::class, 'loadMissingCount')]
class EloquentCollectionLoadMissingCountTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase(): void
    {
        Schema::create((new CollectionLoadMissingCountTestUser)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
        });

        Schema::create((new CollectionLoadMissingCountTestArticle)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->boolean('published')->default(false);
        });
    }

    #[DataProvider('loadMissingCountProvider')]
    public function test_load_missing_count(
        array $initialCounts,
        string|array $relations,
        array $expectedCounts,
    ): void {
        // Arrange

        $user = CollectionLoadMissingCountTestUser::create(['email' => 'john@doe.com']);

        $user->articles()->createMany([
            ['title' => 'Article 1', 'published' => true],
            ['title' => 'Article 2', 'published' => false],
        ]);

        $collection = new Collection([$user]);

        foreach ($initialCounts as $key => $value) {
            $user->setAttribute($key, $value);
        }

        // Act

        $collection->loadMissingCount($relations);

        // Assert

        foreach ($expectedCounts as $key => $value) {
            $this->assertEquals($value, $user->getAttribute($key));
        }
    }

    public static function loadMissingCountProvider(): Generator
    {
        yield 'loads missing count' => [
            'initialCounts' => [],
            'relations' => 'articles',
            'expectedCounts' => ['articles_count' => 2],
        ];

        yield 'does not reload existing count' => [
            'initialCounts' => ['articles_count' => 5], // <- Wrong count but already loaded
            'relations' => 'articles',
            'expectedCounts' => ['articles_count' => 5],
        ];

        yield 'loads missing count with alias' => [
            'initialCounts' => [],
            'relations' => ['articles as posts_count'],
            'expectedCounts' => ['posts_count' => 2], // <- This is the alias name.
        ];

        yield 'works with multiple relations' => [
            'initialCounts' => ['articles_count' => 5],
            'relations' => ['articles', 'articles as posts_count'],
            'expectedCounts' => [
                'articles_count' => 5,
                'posts_count' => 2,
            ],
        ];

        yield 'loads missing count with closure and alias' => [
            'initialCounts' => [],
            'relations' => ['articles as published_articles_count' => fn ($query) => $query->wherePublished()],
            'expectedCounts' => ['published_articles_count' => 1],
        ];

        yield 'loads missing count with closure and no alias' => [
            'initialCounts' => [],
            'relations' => ['articles' => fn ($query) => $query->wherePublished()],
            'expectedCounts' => ['articles_count' => 1],
        ];
    }

    public function test_load_missing_count_filters_models(): void
    {
        // Arrange

        $user1 = CollectionLoadMissingCountTestUser::create(['email' => 'john@doe.com']);
        $user2 = CollectionLoadMissingCountTestUser::create(['email' => 'foo@bar.com']);
        $user1->articles()->create(['title' => 'Article 1']);
        $user2->articles()->create(['title' => 'Article 2']);

        $collection = new Collection([$user1, $user2]);

        $user1->setAttribute('articles_count', 5); // <- Already "loaded"

        DB::enableQueryLog();

        // Act

        $collection->loadMissingCount('articles');

        // Assert

        $this->assertEquals(5, $user1->articles_count);
        $this->assertEquals(1, $user2->articles_count);

        $queryLog = DB::getQueryLog();

        $this->assertCount(1, $queryLog);
        $query = str_replace(['"', '`', '[', ']'], '', $queryLog[0]['query']);

        $this->assertStringContainsString("where users.id in ($user2->id)", $query);
    }
}

class CollectionLoadMissingCountTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function articles(): HasMany
    {
        return $this->hasMany(CollectionLoadMissingCountTestArticle::class, 'user_id');
    }
}

class CollectionLoadMissingCountTestArticle extends Model
{
    protected $table = 'articles';

    protected $guarded = [];

    public $timestamps = false;

    public function scopeWherePublished($query)
    {
        return $query->where('published', true);
    }
}
