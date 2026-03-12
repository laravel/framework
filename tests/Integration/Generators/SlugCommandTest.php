<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Database\Console\Sluggable\SlugCommand;
use Illuminate\Support\Facades\Schema;

class SlugCommandTest extends TestCase
{
    protected $files = [
        'app/Models/Foo.php',
        'database/migrations/*_add_slug_to_foos_table.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createModel();
    }

    public function test_it_creates_migration_and_adds_attribute_to_model()
    {
        $this->artisan(SlugCommand::class, ['model' => 'Foo'])
            ->expectsOutputToContain('Sluggable attribute added to [App\Models\Foo]')
            ->expectsOutputToContain('Migration created. Please review it')
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            "Schema::table('foos', function (Blueprint \$table) {",
            "\$table->string('slug')->unique()->after('id');",
            "\$table->dropColumn('slug');",
        ], 'add_slug_to_foos_table.php');

        $this->assertFileContains([
            'use Illuminate\Database\Eloquent\Attributes\Sluggable;',
            "#[Sluggable(from: 'name')]",
        ], 'app/Models/Foo.php');
    }

    public function test_it_accepts_fully_qualified_class_name()
    {
        $this->artisan(SlugCommand::class, ['model' => 'App\Models\Foo'])
            ->expectsOutputToContain('Sluggable attribute added to [App\Models\Foo]')
            ->assertExitCode(0);

        $this->assertFileContains([
            "#[Sluggable(from: 'name')]",
        ], 'app/Models/Foo.php');
    }

    public function test_it_warns_when_attribute_already_exists()
    {
        $this->addAttributeToModel();

        $this->artisan(SlugCommand::class, ['model' => 'Foo'])
            ->expectsOutputToContain('already has the Sluggable attribute')
            ->assertExitCode(0);
    }

    public function test_it_warns_when_table_already_has_slug_column()
    {
        Schema::create('foos', function ($table) {
            $table->id();
            $table->string('slug');
        });

        $this->artisan(SlugCommand::class, ['model' => 'Foo'])
            ->expectsOutputToContain('already has a slug column')
            ->assertExitCode(0);

        Schema::drop('foos');
    }

    public function test_it_guesses_title_column_from_table()
    {
        Schema::create('foos', function ($table) {
            $table->id();
            $table->string('title');
        });

        $this->artisan(SlugCommand::class, ['model' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            "#[Sluggable(from: 'title')]",
        ], 'app/Models/Foo.php');

        Schema::drop('foos');
    }

    public function test_it_errors_when_migration_already_exists()
    {
        $this->artisan(SlugCommand::class, ['model' => 'Foo'])
            ->assertExitCode(0);

        $this->artisan(SlugCommand::class, ['model' => 'Foo'])
            ->expectsOutputToContain('Migration already exists')
            ->assertExitCode(1);
    }

    public function test_it_errors_when_model_does_not_exist()
    {
        $this->artisan(SlugCommand::class, ['model' => 'NonExistentModel'])
            ->expectsOutputToContain('does not exist')
            ->assertExitCode(1);
    }

    protected function createModel(): void
    {
        $path = $this->app->basePath('app/Models/Foo.php');

        $this->app['files']->ensureDirectoryExists(dirname($path));

        $this->app['files']->put($path, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
}
PHP);

        require_once $path;
    }

    protected function addAttributeToModel(): void
    {
        $path = $this->app->basePath('app/Models/Foo.php');

        $this->app['files']->put($path, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Sluggable;
use Illuminate\Database\Eloquent\Model;

#[Sluggable]
class Foo extends Model
{
}
PHP);
    }
}
