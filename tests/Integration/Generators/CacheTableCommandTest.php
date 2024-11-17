<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Cache\Console\CacheTableCommand;

class CacheTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->artisan(CacheTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const CACHE_TABLENAME = \'cache\';',
            'public const CACHE_LOCKS_TABLENAME = \'cache_locks\';',
            'Schema::create(self::CACHE_TABLENAME, function (Blueprint $table) {',
            'Schema::create(self::CACHE_LOCKS_TABLENAME, function (Blueprint $table) {',
            'Schema::dropIfExists(self::CACHE_TABLENAME);',
            'Schema::dropIfExists(self::CACHE_LOCKS_TABLENAME);',
        ], 'create_cache_table.php');
    }
}
