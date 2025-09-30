<?php

namespace Illuminate\Tests\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class QueryLogTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('content');
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function test_basic_query_logging()
    {
        $logFile = 'test-basic-queries';
        $logPath = storage_path("logs/{$logFile}.log");

        // Clean up any existing log file
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        // Start query logging
        queryLog($logFile);

        // Perform some database operations
        DB::table('users')->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('email', 'john@example.com')->update([
            'name' => 'John Updated',
        ]);

        $users = DB::table('users')->where('email', 'john@example.com')->get();

        DB::table('users')->where('email', 'john@example.com')->delete();

        // Check that log file was created
        $this->assertFileExists($logPath);

        $logContent = file_get_contents($logPath);

        // Assert that specific queries are logged
        $this->assertStringContainsString('insert into `users`', $logContent);
        $this->assertStringContainsString('update `users` set `name` = \'John Updated\'', $logContent);
        $this->assertStringContainsString('select * from `users` where `email` = \'john@example.com\'', $logContent);
        $this->assertStringContainsString('delete from `users` where `email` = \'john@example.com\'', $logContent);

        // Assert that execution time is logged
        $this->assertStringContainsString('Execution Time =', $logContent);
        $this->assertStringContainsString('Date Time =', $logContent);

        // Clean up
        unlink($logPath);
    }

    public function test_query_logging_with_eloquent_models()
    {
        $logFile = 'test-eloquent-queries';
        $logPath = storage_path("logs/{$logFile}.log");

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        queryLog($logFile);

        // Test Eloquent operations
        $user = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $user->update(['name' => 'Jane Updated']);

        $foundUser = TestUser::where('email', 'jane@example.com')->first();

        TestUser::where('email', 'jane@example.com')->delete();

        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);

        // Check for Eloquent-generated queries
        $this->assertStringContainsString('insert into `users`', $logContent);
        $this->assertStringContainsString('update `users` set `name` = \'Jane Updated\'', $logContent);
        $this->assertStringContainsString('select * from `users` where `email` = \'jane@example.com\' limit 1', $logContent);
        $this->assertStringContainsString('delete from `users` where `email` = \'jane@example.com\'', $logContent);

        unlink($logPath);
    }

    public function test_query_logging_with_relationships()
    {
        $logFile = 'test-relationship-queries';
        $logPath = storage_path("logs/{$logFile}.log");

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        queryLog($logFile);

        // Create user and posts to test relationships
        $user = TestUser::create([
            'name' => 'Relationship Test',
            'email' => 'relation@example.com',
        ]);

        $post = TestPost::create([
            'title' => 'Test Post',
            'content' => 'This is a test post',
            'user_id' => $user->id,
        ]);

        // Test relationship queries
        $userWithPosts = TestUser::with('posts')->where('email', 'relation@example.com')->first();
        $posts = $userWithPosts->posts;

        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);

        // Should contain both user query and posts eager loading query
        $this->assertStringContainsString('select * from `users` where `email` = \'relation@example.com\' limit 1', $logContent);
        $this->assertStringContainsString('select * from `posts` where `posts`.`user_id` in (', $logContent);

        unlink($logPath);
    }

    public function test_query_logging_with_complex_queries()
    {
        $logFile = 'test-complex-queries';
        $logPath = storage_path("logs/{$logFile}.log");

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        queryLog($logFile);

        // Test complex queries with joins, aggregates, etc.
        DB::table('users')->insert([
            ['name' => 'User 1', 'email' => 'user1@example.com', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'User 2', 'email' => 'user2@example.com', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'User 3', 'email' => 'user3@example.com', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Complex query with join and aggregate
        $results = DB::table('users')
            ->select('users.name', DB::raw('COUNT(posts.id) as post_count'))
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->groupBy('users.id', 'users.name')
            ->having('post_count', '>=', 0)
            ->orderBy('users.name')
            ->get();

        // Query with raw expressions and bindings
        DB::table('products')->whereRaw('price > ? AND active = ?', [100, true])->get();

        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);

        $this->assertStringContainsString('left join `posts` on `users`.`id` = `posts`.`user_id`', $logContent);
        $this->assertStringContainsString('group by `users`.`id`, `users`.`name`', $logContent);
        $this->assertStringContainsString('having `post_count` >= 0', $logContent);
        $this->assertStringContainsString('price > 100 AND active = 1', $logContent);

        unlink($logPath);
    }

    public function test_query_logging_with_backtrace()
    {
        $logFile = 'test-backtrace-queries';
        $logPath = storage_path("logs/{$logFile}.log");

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        // Enable backtrace
        queryLog($logFile, true);

        DB::table('users')->insert([
            'name' => 'Backtrace Test',
            'email' => 'backtrace@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);


        // Should contain backtrace information (file paths and line numbers)
        $this->assertStringContainsString('.php', $logContent);
        $this->assertStringContainsString('line', $logContent);

        unlink($logPath);
    }

    public function test_query_logging_with_specific_channel()
    {
        $logFile = 'test-channel-queries';
        $logPath = storage_path("logs/{$logFile}.log");

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        // Log to a specific channel
        queryLog($logFile, false, 'stack');

        DB::table('products')->insert([
            'name' => 'Test Product',
            'price' => 99.99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);

        $this->assertStringContainsString('insert into `products`', $logContent);
        $this->assertStringContainsString('Test Product', $logContent);
        $this->assertStringContainsString('99.99', $logContent);

        unlink($logPath);
    }

    public function test_query_logging_with_query_builder_methods()
    {
        $logFile = 'test-query-builder-queries';
        $logPath = storage_path("logs/{$logFile}.log");

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        queryLog($logFile);

        // Test various query builder methods
        DB::table('users')->where('name', 'like', '%test%')->get();
        DB::table('users')->whereIn('email', ['test1@example.com', 'test2@example.com'])->get();
        DB::table('users')->whereBetween('id', [1, 10])->get();
        DB::table('users')->select('name', 'email')->distinct()->get();
        DB::table('users')->orderBy('name', 'desc')->limit(5)->get();

        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);

        $this->assertStringContainsString('like \'%test%\'', $logContent);
        $this->assertStringContainsString('where `email` in', $logContent); // Changed from 'where in'
        $this->assertStringContainsString('where `id` between', $logContent); // Changed from 'where between'
        $this->assertStringContainsString('distinct', $logContent);
        $this->assertStringContainsString('order by `name` desc', $logContent);
        $this->assertStringContainsString('limit 5', $logContent);

        unlink($logPath);
    }
}

class TestUser extends Model
{
    public $table = 'users';

    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }
}

class TestPost extends Model
{
    public $table = 'posts';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}
