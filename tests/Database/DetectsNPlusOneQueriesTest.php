<?php

namespace Illuminate\Tests\Database;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Listeners\DetectsNPlusOneQueries;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DetectsNPlusOneQueriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new class extends Container
        {
            public function isLocal()
            {
                return true;
            }
        };

        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        $app->instance('config', new ConfigRepository([
            'app' => ['debug' => true],
            'database' => ['detect_n_plus_one' => true],
        ]));

        $db = new DB;
        // Use SQLite in-memory so the test runs with or without Docker (no MySQL required).
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'default');
        $db->setAsGlobal();
        $db->setEventDispatcher(new Dispatcher);
        $db->bootEloquent();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function test_it_logs_warning_for_potential_n_plus_one_queries()
    {
        $listener = new DetectsNPlusOneQueries;

        $logger = m::mock();
        $logger->shouldReceive('warning')
            ->once()
            ->with('Possible N+1 query detected.', m::on(function (array $context) {
                return isset($context['normalized_sql'], $context['count'])
                    && $context['count'] === 3
                    && str_contains($context['normalized_sql'], 'from "comments"');
            }));

        Log::swap($logger);

        DB::connection()->listen(function (QueryExecuted $event) use ($listener) {
            $listener->handle($event);
        });

        // Seed posts with a single insert so the first query to reach count 3 is the N+1 comments query.
        DB::table('posts')->insert([
            ['title' => 'Post 1'],
            ['title' => 'Post 2'],
            ['title' => 'Post 3'],
        ]);

        // Classic N+1: one query for posts, then one query per post to count comments (3 identical queries).
        $posts = PostForNPlusOneTest::all();

        foreach ($posts as $post) {
            $post->comments()->count();
        }
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function createSchema()
    {
        DB::schema()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
        });

        DB::schema()->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->string('body')->nullable();
        });
    }
}

class PostForNPlusOneTest extends Eloquent
{
    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(CommentForNPlusOneTest::class, 'post_id');
    }
}

class CommentForNPlusOneTest extends Eloquent
{
    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(PostForNPlusOneTest::class, 'post_id');
    }
}
