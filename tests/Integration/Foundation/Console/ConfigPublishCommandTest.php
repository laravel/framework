<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\package_path;

class ConfigPublishCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected array $files = [
        'config-stubs/*.php',
    ];

    /**
     * Get application providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    #[\Override]
    protected function getApplicationProviders($app)
    {
        return ServiceProvider::defaultProviders()->toArray();
    }

    /**
     * Resolve application core configuration implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    #[\Override]
    protected function resolveApplicationConfiguration($app)
    {
        $app->instance(LoadConfiguration::class, new LoadConfiguration());

        parent::resolveApplicationConfiguration($app);
    }

    public function testItCanPublishConfigFilesWhenConfiguredWithDontMergeFrameworkConfiguration()
    {
        $this->app->useConfigPath(base_path('config-stubs'));

        $this->app->dontMergeFrameworkConfiguration();

        $this->artisan('config:publish', ['--all' => true])->assertOk();

        foreach ([
            'app', 'auth', 'broadcasting', 'cache', 'cors',
            'database', 'filesystems', 'hashing', 'logging',
            'mail', 'queue', 'services', 'session', 'view',
        ] as $file) {
            $this->assertFilenameExists("config-stubs/{$file}.php");
            $this->assertStringContainsString(
                file_get_contents(package_path(['config', "{$file}.php"])), file_get_contents(config_path("{$file}.php"))
            );
        }
    }
}
