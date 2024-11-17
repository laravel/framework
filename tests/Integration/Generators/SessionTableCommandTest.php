<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Session\Console\SessionTableCommand;

class SessionTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->artisan(SessionTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const TABLENAME = \'sessions\';',
            'Schema::create(self::TABLENAME, function (Blueprint $table) {',
            'Schema::dropIfExists(self::TABLENAME);',
        ], 'create_sessions_table.php');
    }
}
