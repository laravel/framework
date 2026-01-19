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

#[CoversMethod(Collection::class, 'loadMissingAggregate')]
class EloquentCollectionLoadMissingAggregateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase(): void
    {
        Schema::create((new CollectionLoadMissingAggregateTestUser)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
        });

        Schema::create((new CollectionLoadMissingAggregateTestArticle)->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->integer('views')->default(0);
            $table->boolean('published')->default(false);
        });
    }

    #[DataProvider('loadMissingAggregateProvider')]
    public function test_load_missing_aggregate(
        array $initialValues,
        string|array $relations,
        string $column,
        string $function,
        array $expectedValues,
    ): void {
        // Arrange

        $user = CollectionLoadMissingAggregateTestUser::create(['email' => 'john@doe.com']);

        $user->articles()->createMany([
            ['title' => 'Article 1', 'views' => 10, 'published' => true],
            ['title' => 'Article 2', 'views' => 20, 'published' => false],
        ]);

        $collection = new Collection([$user]);

        foreach ($initialValues as $key => $value) {
            $user->setAttribute($key, $value);
        }

        // Act

        $collection->loadMissingAggregate($relations, $column, $function);

        // Assert

        foreach ($expectedValues as $key => $value) {
            $this->assertEquals($value, $user->getAttribute($key));
        }
    }

    public static function loadMissingAggregateProvider(): Generator
    {
        yield 'loads missing max' => [
            'initialValues' => [],
            'relations' => 'articles',
            'column' => 'views',
            'function' => 'max',
            'expectedValues' => ['articles_max_views' => 20],
        ];

        yield 'does not reload existing aggregate' => [
            'initialValues' => ['articles_max_views' => 100], // <- Already "loaded"
            'relations' => 'articles',
            'column' => 'views',
            'function' => 'max',
            'expectedValues' => ['articles_max_views' => 100],
        ];

        yield 'loads missing sum' => [
            'initialValues' => [],
            'relations' => 'articles',
            'column' => 'views',
            'function' => 'sum',
            'expectedValues' => ['articles_sum_views' => 30],
        ];

        yield 'loads missing aggregate with alias' => [
            'initialValues' => [],
            'relations' => ['articles as total_views'],
            'column' => 'views',
            'function' => 'sum',
            'expectedValues' => ['total_views' => 30],
        ];

        yield 'works with multiple relations' => [
            'initialValues' => ['articles_max_views' => 100],
            'relations' => ['articles', 'articles as max_views'],
            'column' => 'views',
            'function' => 'max',
            'expectedValues' => [
                'articles_max_views' => 100,
                'max_views' => 20,
            ],
        ];

        yield 'loads missing aggregate with closure and alias' => [
            'initialValues' => [],
            'relations' => ['articles as published_total_views' => fn ($query) => $query->wherePublished()],
            'column' => 'views',
            'function' => 'sum',
            'expectedValues' => ['published_total_views' => 10],
        ];
    }

    public function test_load_missing_aggregate_filters_models(): void
    {
        // Arrange

        $user1 = CollectionLoadMissingAggregateTestUser::create(['email' => 'john@doe.com']);
        $user2 = CollectionLoadMissingAggregateTestUser::create(['email' => 'foo@bar.com']);
        $user1->articles()->create(['title' => 'Article 1', 'views' => 10]);
        $user2->articles()->create(['title' => 'Article 2', 'views' => 20]);

        $collection = new Collection([$user1, $user2]);

        $user1->setAttribute('articles_sum_views', 100); // <- Already "loaded"

        DB::enableQueryLog();

        // Act

        $collection->loadMissingAggregate('articles', 'views', 'sum');

        // Assert

        $this->assertEquals(100, $user1->articles_sum_views);
        $this->assertEquals(20, $user2->articles_sum_views);

        $queryLog = DB::getQueryLog();

        $this->assertCount(1, $queryLog);
        $query = str_replace(['"', '`', '[', ']'], '', $queryLog[0]['query']);

        $this->assertStringContainsString("where users.id in ($user2->id)", $query);
    }
}

class CollectionLoadMissingAggregateTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function articles(): HasMany
    {
        return $this->hasMany(CollectionLoadMissingAggregateTestArticle::class, 'user_id');
    }
}

class CollectionLoadMissingAggregateTestArticle extends Model
{
    protected $table = 'articles';

    protected $guarded = [];

    public $timestamps = false;

    public function scopeWherePublished($query)
    {
        return $query->where('published', true);
    }
}
