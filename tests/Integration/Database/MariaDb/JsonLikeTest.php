<?php

namespace Illuminate\Tests\Integration\Database\MariaDb;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_mysql')]
class JsonLikeTest extends MariaDbTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->json('data');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('tasks');
    }

    public function testJsonLikeWithEmoji()
    {
        // Test that LIKE queries work correctly with emojis in JSON fields
        // This verifies that json_value() handles emojis correctly (unlike json_unquote)
        DB::table('tasks')->insert([
            ['data' => '{"status":"Building started 🔨"}'],
            ['data' => '{"status":"Tests passed ✅"}'],
            ['data' => '{"status":"Deployment complete 🌎"}'],
        ]);

        // Search for records containing the hammer emoji
        $buildCount = DB::table('tasks')
            ->where('data->status', 'like', '%🔨%')
            ->count();
        $this->assertSame(1, $buildCount, 'Should find 1 record with hammer emoji');

        // Search for records containing "Tests" with emoji
        $testsCount = DB::table('tasks')
            ->where('data->status', 'like', '%Tests%')
            ->count();
        $this->assertSame(1, $testsCount, 'Should find 1 record with "Tests"');

        // Search for records containing rocket emoji
        $deployCount = DB::table('tasks')
            ->where('data->status', 'like', '%🌎%')
            ->count();
        $this->assertSame(1, $deployCount, 'Should find 1 record with globe emoji');

        // Verify we can find text before emoji
        $completeCount = DB::table('tasks')
            ->where('data->status', 'like', '%complete%')
            ->count();
        $this->assertSame(1, $completeCount, 'Should find 1 record with "complete" before emoji');
    }
}
