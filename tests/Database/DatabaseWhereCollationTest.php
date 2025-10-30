<?php

namespace Tests\Database;

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class DatabaseWhereCollationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_invalid_collation_name_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);

        DB::table('_test_collation')->whereCollation('name', '=', 'test', 'utf8mb4; DROP TABLE users;', 'utf8mb4');
    }
}
