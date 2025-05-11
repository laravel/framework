<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class DatabaseManagerTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        tap(new Manager)->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ])->bootEloquent();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Model::unsetConnectionResolver();
    }
    public function testSanitizeSqlLike()
    {
        $this->assertEquals('100\%', DB::sanitizeSqlLike('100%'));
        $this->assertEquals('snake\_cased\_string', DB::sanitizeSqlLike('snake_cased_string'));
        $this->assertEquals('great!!', DB::sanitizeSqlLike('great!', '!'));
        $this->assertEquals('C:\\\\Programs\\\\MsPaint', DB::sanitizeSqlLike('C:\\Programs\\MsPaint'));
        $this->assertEquals('normal string 42', DB::sanitizeSqlLike('normal string 42'));
    }

    public function testSanitizeSqlLikeWithCustomEscapeCharacter()
    {
        $this->assertEquals('100!%', DB::sanitizeSqlLike('100%', '!'));
        $this->assertEquals('snake!_cased!_string', DB::sanitizeSqlLike('snake_cased_string', '!'));
        $this->assertEquals('great!!', DB::sanitizeSqlLike('great!', '!'));
        $this->assertEquals('C:\\Programs\\MsPaint', DB::sanitizeSqlLike('C:\\Programs\\MsPaint', '!'));
        $this->assertEquals('normal string 42', DB::sanitizeSqlLike('normal string 42', '!'));
    }

    public function testSanitizeSqlLikeWithWildcardAsEscapeCharacter()
    {
        $this->assertEquals('1__000_%', DB::sanitizeSqlLike('1_000%', '_'));
        $this->assertEquals('1%_000%%', DB::sanitizeSqlLike('1_000%', '%'));
    }

    public function testSanitizeSqlLikeExampleUseCase()
    {
        [$binding] = Post::query()->searchAsScope('20% _reduction_!')->getBindings();

        $this->assertSame('20\% \_reduction\_!', $binding);
    }
}

class Post extends Model
{
    public function scopeSearchAsScope($query, string $term)
    {
        $term = DB::sanitizeSqlLike($term);

        return $query->where('title', 'LIKE', $term);
    }
}
