<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase(['sqlite', 'pgsql', 'sqlsrv'])]
class SchemaPartialUniqueIndexTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('slug');
            $table->softDeletes();
            $table->unique(['user_id', 'slug'])->whereNull('deleted_at');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('posts');
    }

    public function testActiveDuplicateFails()
    {
        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => null,
        ]);

        $this->expectException(QueryException::class);

        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => null,
        ]);
    }

    public function testDuplicateAllowedAfterSoftDelete()
    {
        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => now(),
        ]);

        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => null,
        ]);

        $this->assertSame(2, DB::table('posts')->count());
    }

    public function testMultipleSoftDeletedRowsWithSameKeyAllowed()
    {
        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => now()->subDay(),
        ]);

        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => now(),
        ]);

        $this->assertSame(2, DB::table('posts')->whereNotNull('deleted_at')->count());
    }

    public function testRestoreFailsWhenActiveDuplicateExists()
    {
        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => now(),
        ]);

        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => null,
        ]);

        $this->expectException(QueryException::class);

        DB::table('posts')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);
    }

    public function testPartialUniqueIndexCanBeDropped()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique('posts_user_id_slug_unique');
        });

        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => null,
        ]);

        DB::table('posts')->insert([
            'user_id' => 1,
            'slug' => 'hello',
            'deleted_at' => null,
        ]);

        $this->assertSame(2, DB::table('posts')->count());
    }
}
