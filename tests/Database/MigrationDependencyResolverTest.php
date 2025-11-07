<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Migrations\MigrationDependencyResolver;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class MigrationDependencyResolverTest extends TestCase
{
    protected $filesystem;
    protected $migrator;
    protected $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = m::mock(Filesystem::class);
        $this->migrator = m::mock(Migrator::class);
        $this->resolver = new MigrationDependencyResolver($this->filesystem, $this->migrator);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testExtractsForeignKeyDependencies()
    {
        $migrationContent = '<?php

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create("posts", function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->foreignId("user_id")->constrained();
            $table->timestamps();
        });
    }
}';

        $paths = ['/path/to/migrations'];
        $migrations = [
            '2024_01_01_000000_create_users_table' => '/path/to/migrations/2024_01_01_000000_create_users_table.php',
            '2024_01_02_000000_create_posts_table' => '/path/to/migrations/2024_01_02_000000_create_posts_table.php',
        ];

        $this->migrator->shouldReceive('getMigrationFiles')
            ->with($paths)
            ->andReturn($migrations);

        // Mock file contents
        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_01_000000_create_users_table.php')
            ->andReturn('<?php
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create("users", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->timestamps();
        });
    }
}');

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_02_000000_create_posts_table.php')
            ->andReturn($migrationContent);

        $analysis = $this->resolver->analyzeDependencies($paths);

        $this->assertArrayHasKey('dependencies', $analysis);
        $this->assertArrayHasKey('tables', $analysis);
        $this->assertArrayHasKey('foreignKeys', $analysis);

        // The posts table should depend on users table
        $this->assertContains('2024_01_01_000000_create_users_table',
            $analysis['dependencies']['2024_01_02_000000_create_posts_table']);
    }

    public function testDetectsTableCreation()
    {
        $migrationContent = '<?php

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create("users", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists("users");
    }
}';

        $paths = ['/path/to/migrations'];
        $migrations = [
            '2024_01_01_000000_create_users_table' => '/path/to/migrations/2024_01_01_000000_create_users_table.php',
        ];

        $this->migrator->shouldReceive('getMigrationFiles')
            ->with($paths)
            ->andReturn($migrations);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_01_000000_create_users_table.php')
            ->andReturn($migrationContent);

        $analysis = $this->resolver->analyzeDependencies($paths);

        $this->assertContains('users', $analysis['tables']['2024_01_01_000000_create_users_table']['created']);
    }

    public function testDetectsCircularDependencies()
    {
        $migration1Content = '<?php

class CreateTableA extends Migration
{
    public function up()
    {
        Schema::create("table_a", function (Blueprint $table) {
            $table->id();
            $table->foreignId("table_b_id")->constrained("table_b");
        });
    }
}';

        $migration2Content = '<?php

class CreateTableB extends Migration
{
    public function up()
    {
        Schema::create("table_b", function (Blueprint $table) {
            $table->id();
            $table->foreignId("table_a_id")->constrained("table_a");
        });
    }
}';

        $paths = ['/path/to/migrations'];
        $migrations = [
            '2024_01_01_000000_create_table_a' => '/path/to/migrations/2024_01_01_000000_create_table_a.php',
            '2024_01_02_000000_create_table_b' => '/path/to/migrations/2024_01_02_000000_create_table_b.php',
        ];

        $this->migrator->shouldReceive('getMigrationFiles')
            ->with($paths)
            ->andReturn($migrations);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_01_000000_create_table_a.php')
            ->andReturn($migration1Content);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_02_000000_create_table_b.php')
            ->andReturn($migration2Content);

        $analysis = $this->resolver->analyzeDependencies($paths);

        $this->assertNotEmpty($analysis['conflicts']);

        // Should detect circular dependency conflict
        $circularConflicts = array_filter($analysis['conflicts'], function ($conflict) {
            return $conflict['type'] === 'circular_dependency';
        });

        $this->assertNotEmpty($circularConflicts);
    }

    public function testDetectsMissingTableDependencies()
    {
        $migrationContent = '<?php

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create("posts", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users");
            $table->timestamps();
        });
    }
}';

        $paths = ['/path/to/migrations'];
        $migrations = [
            '2024_01_02_000000_create_posts_table' => '/path/to/migrations/2024_01_02_000000_create_posts_table.php',
        ];

        $this->migrator->shouldReceive('getMigrationFiles')
            ->with($paths)
            ->andReturn($migrations);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_02_000000_create_posts_table.php')
            ->andReturn($migrationContent);

        $analysis = $this->resolver->analyzeDependencies($paths);

        $this->assertNotEmpty($analysis['conflicts']);

        // Should detect missing table conflict
        $missingTableConflicts = array_filter($analysis['conflicts'], function ($conflict) {
            return $conflict['type'] === 'missing_table';
        });

        $this->assertNotEmpty($missingTableConflicts);
    }

    public function testSuggestsOptimalOrder()
    {
        $usersMigration = '<?php
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create("users", function (Blueprint $table) {
            $table->id();
            $table->string("name");
        });
    }
}';

        $postsMigration = '<?php
class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create("posts", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained();
        });
    }
}';

        $commentsMigration = '<?php
class CreateCommentsTable extends Migration
{
    public function up()
    {
        Schema::create("comments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("post_id")->constrained();
            $table->foreignId("user_id")->constrained();
        });
    }
}';

        $paths = ['/path/to/migrations'];
        $migrations = [
            '2024_01_03_000000_create_comments_table' => '/path/to/migrations/2024_01_03_000000_create_comments_table.php',
            '2024_01_01_000000_create_users_table' => '/path/to/migrations/2024_01_01_000000_create_users_table.php',
            '2024_01_02_000000_create_posts_table' => '/path/to/migrations/2024_01_02_000000_create_posts_table.php',
        ];

        $this->migrator->shouldReceive('getMigrationFiles')
            ->with($paths)
            ->andReturn($migrations);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_01_000000_create_users_table.php')
            ->andReturn($usersMigration);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_02_000000_create_posts_table.php')
            ->andReturn($postsMigration);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_03_000000_create_comments_table.php')
            ->andReturn($commentsMigration);

        $analysis = $this->resolver->analyzeDependencies($paths);

        $suggestedOrder = $analysis['suggestedOrder'];

        // Users should come first
        $this->assertEquals('2024_01_01_000000_create_users_table', $suggestedOrder[0]);

        // Posts should come second
        $this->assertEquals('2024_01_02_000000_create_posts_table', $suggestedOrder[1]);

        // Comments should come last
        $this->assertEquals('2024_01_03_000000_create_comments_table', $suggestedOrder[2]);
    }

    public function testExtractsForeignKeyInformation()
    {
        $migrationContent = '<?php

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create("posts", function (Blueprint $table) {
            $table->id();
            $table->foreign("user_id")->references("id")->on("users");
            $table->foreignId("category_id")->constrained("categories");
        });
    }
}';

        $paths = ['/path/to/migrations'];
        $migrations = [
            '2024_01_02_000000_create_posts_table' => '/path/to/migrations/2024_01_02_000000_create_posts_table.php',
        ];

        $this->migrator->shouldReceive('getMigrationFiles')
            ->with($paths)
            ->andReturn($migrations);

        $this->filesystem->shouldReceive('get')
            ->with('/path/to/migrations/2024_01_02_000000_create_posts_table.php')
            ->andReturn($migrationContent);

        $analysis = $this->resolver->analyzeDependencies($paths);

        $foreignKeys = $analysis['foreignKeys']['2024_01_02_000000_create_posts_table'];

        $this->assertCount(2, $foreignKeys);

        $this->assertEquals('user_id', $foreignKeys[0]['column']);
        $this->assertEquals('users', $foreignKeys[0]['on']);

        $this->assertEquals('category_id', $foreignKeys[1]['column']);
        $this->assertEquals('categories', $foreignKeys[1]['on']);
    }

    public function testReturnsJsonFormat()
    {
        $paths = ['/path/to/migrations'];
        $migrations = [];

        $this->migrator->shouldReceive('getMigrationFiles')
            ->with($paths)
            ->andReturn($migrations);

        $json = $this->resolver->getAnalysisJson($paths);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('dependencies', $decoded);
        $this->assertArrayHasKey('tables', $decoded);
        $this->assertArrayHasKey('foreignKeys', $decoded);
        $this->assertArrayHasKey('conflicts', $decoded);
        $this->assertArrayHasKey('suggestedOrder', $decoded);
    }
}
