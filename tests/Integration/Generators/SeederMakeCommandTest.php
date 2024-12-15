<?php

namespace Illuminate\Tests\Integration\Generators;

class SeederMakeCommandTest extends TestCase
{
    protected $files = [
        'database/seeders/FooSeeder.php',
    ];

    public function testItCanGenerateSeederFile()
    {
        $this->artisan('make:seeder', ['name' => 'FooSeeder'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Database\Seeders;',
            'use Illuminate\Database\Seeder;',
            'class FooSeeder extends Seeder',
            'public function run()',
        ], 'database/seeders/FooSeeder.php');
    }


    public function testItCanAutoRegisterSeederInDatabaseSeeder()
    {
        // Prepare: Create a mock DatabaseSeeder file
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');
        $initialContent = "<?php\n\nnamespace Database\Seeders;\n\nuse Illuminate\Database\Seeder;\n\nclass DatabaseSeeder extends Seeder\n{\n    public function run()\n    {\n        \$this->call([\n            // Existing seeders\n        ]);\n    }\n}";

        // Ensure the DatabaseSeeder exists
        file_put_contents($databaseSeederPath, $initialContent);

        // Run the make:seeder command with auto-register option
        $this->artisan('make:seeder', [
            'name' => 'FooSeeder',
            '--auto-register' => true
        ])->assertExitCode(0);

        // Read the updated DatabaseSeeder
        $updatedContent = file_get_contents($databaseSeederPath);

        // Assert that the new seeder is registered
        $this->assertStringContainsString('FooSeeder::class,', $updatedContent);

        // Cleanup
        $this->files[] = 'database/seeders/FooSeeder.php';
    }

    public function testItDoesNotRegisterDuplicateSeeder()
    {
        // Prepare: Create a DatabaseSeeder with existing seeder
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');
        $initialContent = "<?php\n\nnamespace Database\Seeders;\n\nuse Illuminate\Database\Seeder;\n\nclass DatabaseSeeder extends Seeder\n{\n    public function run()\n    {\n        \$this->call([\n            FooSeeder::class,\n        ]);\n    }\n}";

        file_put_contents($databaseSeederPath, $initialContent);

        // Run the make:seeder command with auto-register option
        $this->artisan('make:seeder', [
            'name' => 'FooSeeder',
            '--auto-register' => true
        ])->expectsOutput("Seeder 'FooSeeder' is already registered in DatabaseSeeder.");

        // Verify the content remains unchanged
        $finalContent = file_get_contents($databaseSeederPath);
        $this->assertEquals($initialContent, $finalContent);
    }
}
