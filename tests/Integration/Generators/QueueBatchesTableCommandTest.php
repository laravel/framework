<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Queue\Console\BatchesTableCommand;

class QueueBatchesTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->artisan(BatchesTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const TABLENAME = \'job_batches\';',
            'Schema::create(self::TABLENAME, function (Blueprint $table) {',
            'Schema::dropIfExists(self::TABLENAME);',
        ], 'create_job_batches_table.php');
    }
}
