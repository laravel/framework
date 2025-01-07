<?php

namespace Illuminate\Tests\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class MigrateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure a clean state before each test
        Schema::dropIfExists('test_table_1');
        Schema::dropIfExists('test_table_2');

        // Create the migrations table if it doesn't exist
        if (!Schema::hasTable('migrations')) {
            Schema::create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }

        // Truncate the migrations table
        DB::table('migrations')->truncate();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up after each test
        Schema::dropIfExists('test_table_1');
        Schema::dropIfExists('test_table_2');

        // Ensure migrations table exists before truncating
        if (Schema::hasTable('migrations')) {
            DB::table('migrations')->truncate();
        }

        parent::tearDown();
    }

    /**
     * Creates migration file with multiple Schema::create calls and gives it proper permission
     * 
     * @return string
     */
    public function makeMigration(): string
    {
        $migrationPath = database_path('migrations');
        $migrationFile = $migrationPath . '/2025_01_06_000000_test_migration.php';

        if (!is_dir($migrationPath)) {
            mkdir($migrationPath, 0777, true);
        }

        file_put_contents($migrationFile, <<<PHP
        <?php

        use Illuminate\Support\Facades\Schema;
        use Illuminate\Database\Migrations\Migration;

        return new class extends Migration {
            public function up()
            {
                Schema::create('test_table_1', function (\$table) {
                    \$table->id();
                });

                // Simulate an error in the second table creation
                Schema::create('test_table_2', function (\$table) {
                     \$table->id();
                     \$table->string('test_duplicate_column');
                     \$table->string('test_duplicate_column');
                });
            }

            public function down()
            {
                Schema::dropIfExists('test_table_1');
                Schema::dropIfExists('test_table_2');
            }
        };
        PHP);

        return $migrationFile;
    }

    public function test_migrate_command_rolls_back_on_failure()
    {
        $migrationFile = $this->makeMigration();

        // Run the migrate command and expect it to fail
        $this->artisan('migrate')
            ->expectsOutputToContain('SQLSTATE');

        // Assert the first table was not created
        $this->assertFalse(Schema::hasTable('test_table_1'), "Table 'test_table_1' should not exist after a failed migration.");

        // Assert the second table was not created
        $this->assertFalse(Schema::hasTable('test_table_2'), "Table 'test_table_2' should not exist after a failed migration.");

        // Assert the migration is not logged in the migrations table
        $this->assertFalse(
            DB::table('migrations')->where('migration', '2025_01_06_000000_test_migration')->exists(),
            "The migration should not be logged in the migrations table after failure."
        );

        // Clean up the temporary migration file
        unlink($migrationFile);
    }
}
