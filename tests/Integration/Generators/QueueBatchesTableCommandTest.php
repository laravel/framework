<?php

namespace Illuminate\Tests\Integration\Generators;

class QueueBatchesTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->artisan('queue:batches-table')->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'job_batches\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'job_batches\');',
        ], 'create_job_batches_table.php');
    }
}
