<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Queue\Console\FailedTableCommand;

class QueueFailedTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->artisan(FailedTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const TABLENAME = \'failed_jobs\';',
            'Schema::create(self::TABLENAME, function (Blueprint $table) {',
            'Schema::dropIfExists(self::TABLENAME);',
        ], 'create_failed_jobs_table.php');
    }
}
