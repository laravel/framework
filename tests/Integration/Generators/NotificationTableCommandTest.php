<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Notifications\Console\NotificationTableCommand;

class NotificationTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->artisan(NotificationTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const TABLENAME = \'notifications\';',
            'Schema::create(self::TABLENAME, function (Blueprint $table) {',
            'Schema::dropIfExists(self::TABLENAME);',
        ], 'create_notifications_table.php');
    }
}
