<?php

namespace Illuminate\Tests\Database;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;

class DatabaseWhereCollationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('Collation conversion not supported on SQLite.');
        }

        DB::statement('CREATE TEMPORARY TABLE _test_collation (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_cs,
            url VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        )');

        DB::table('_test_collation')->insert([
            ['name' => 'simple', 'url' => 'https://example.com'],
            ['name' => 'سلام', 'url' => 'https://example.com/fa'],
        ]);
    }

    public function test_where_collation_can_match_ascii_value()
    {
        $record = DB::table('_test_collation')
            ->whereCollation('name', 'simple')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('https://example.com', $record->url);
    }

    public function test_where_collation_can_match_unicode_value()
    {
        $record = DB::table('_test_collation')
            ->whereCollation('name', '=', 'سلام')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('https://example.com/fa', $record->url);
    }

    public function test_invalid_charset_name_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid charset name');

        DB::table('_test_collation')->whereCollation('name', '=', 'test', 'utf8mb4', 'invalid-charset');
    }

    public function test_invalid_collation_name_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid collation name');

        DB::table('_test_collation')->whereCollation('name', '=', 'test', 'invalid_collation', 'utf8mb4');
    }


    protected function tearDown(): void
    {
        try {
            DB::statement('DROP TEMPORARY TABLE IF EXISTS _test_collation');
        } catch (\Throwable $e) {
            // ignore
        }

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        if (class_exists(AliasLoader::class)) {
            $ref = new \ReflectionClass(AliasLoader::class);
            if ($ref->hasProperty('instance')) {
                $instance = $ref->getProperty('instance');
                $instance->setAccessible(true);
                $instance->setValue(null, null);
            }
        }

        while (set_error_handler(fn () => null)) {
            restore_error_handler();
        }
        while (set_exception_handler(fn () => null)) {
            restore_exception_handler();
        }

        parent::tearDown();
    }
}
