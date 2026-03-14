<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Tests\Integration\Generators\TestCase;
use LogicException;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ConfigCacheCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'bootstrap/cache/config.php',
        'config/testconfig.php',
        '.env',
        '.env.production',
    ];

    protected function setUp(): void
    {
        $files = new Filesystem;

        $this->afterApplicationCreated(function () use ($files) {
            $files->ensureDirectoryExists($this->app->configPath());
        });

        $this->beforeApplicationDestroyed(function () use ($files) {
            $files->delete($this->app->configPath('testconfig.php'));
        });

        parent::setUp();

        $this->resetLoadEnvironmentVariablesState();
    }

    public function testConfigurationCanBeCachedSuccessfully()
    {
        $files = new Filesystem;
        $files->put($this->app->configPath('testconfig.php'), <<<'PHP'
            <?php

            return [
                'string' => 'value',
                'number' => 123,
                'boolean' => true,
                'array' => ['foo', 'bar'],
                'from_env' => env('SOMETHING_FROM_ENV', 10),
                'nested' => [
                    'key' => 'value',
                ],
            ];
            PHP
        );

        $this->artisan('config:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('Configuration cached successfully');

        $this->assertFileExists($this->app->getCachedConfigPath());
    }

    public function testConfigurationCacheFailsWithNonSerializableValue()
    {
        $files = new Filesystem;
        $files->put($this->app->configPath('testconfig.php'), <<<'PHP'
            <?php

            return [
                'closure' => function () {
                    return 'test';
                },
            ];
            PHP
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Your configuration files could not be serialized because the value at "testconfig.closure" is non-serializable.');

        $this->artisan('config:cache');
    }

    public function testConfigurationCacheFailsWithNestedNonSerializableValue()
    {
        $files = new Filesystem;
        $files->put($this->app->configPath('testconfig.php'), <<<'PHP'
            <?php

            return [
                'nested' => [
                    'deep' => [
                        'closure' => function () {
                            return 'test';
                        },
                    ],
                ],
            ];
            PHP
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Your configuration files could not be serialized because the value at "testconfig.nested.deep.closure" is non-serializable.');

        $this->artisan('config:cache');
    }

    public function testConfigurationCacheIsDeletedWhenSerializationFails()
    {
        $files = new Filesystem;
        $files->put($this->app->configPath('testconfig.php'), <<<'PHP'
            <?php

            return [
                'closure' => function () {
                    return 'test';
                },
            ];
            PHP
        );

        try {
            $this->artisan('config:cache');
            $this->fail('should have thrown an exception');
        } catch (LogicException) {
            // Expected exception
        }

        $this->assertFileDoesNotExist($this->app->getCachedConfigPath());
    }

    public function testConfigurationCacheDoesNotUseEnvironmentSpecificFilesWhenAppEnvComesFromDotEnv()
    {
        $files = new Filesystem;

        $files->put($this->app->environmentFilePath(), <<<'ENV'
            APP_ENV=production
            ENV
        );

        $files->put($this->app->environmentPath().'/.env.production', <<<'ENV'
            SOMETHING_FROM_ENV=from_production
            ENV
        );

        $files->put($this->app->configPath('testconfig.php'), <<<'PHP'
            <?php

            return [
                'from_env' => env('SOMETHING_FROM_ENV', 'from_default'),
            ];
            PHP
        );

        $this->unsetExternalEnvironmentVariable('APP_ENV');
        $this->unsetExternalEnvironmentVariable('SOMETHING_FROM_ENV');

        (new LoadEnvironmentVariables)->bootstrap($this->app);

        $this->artisan('config:cache')->assertSuccessful();

        $config = require $this->app->getCachedConfigPath();

        $this->assertSame('from_default', $config['testconfig']['from_env']);
    }

    public function testConfigurationCacheUsesEnvironmentSpecificFilesWhenAppEnvIsExternallyProvided()
    {
        $files = new Filesystem;

        $files->put($this->app->environmentFilePath(), <<<'ENV'
            APP_ENV=local
            ENV
        );

        $files->put($this->app->environmentPath().'/.env.production', <<<'ENV'
            SOMETHING_FROM_ENV=from_production
            ENV
        );

        $files->put($this->app->configPath('testconfig.php'), <<<'PHP'
            <?php

            return [
                'from_env' => env('SOMETHING_FROM_ENV'),
            ];
            PHP
        );

        $this->setExternalEnvironmentVariable('APP_ENV', 'production');
        $this->unsetExternalEnvironmentVariable('SOMETHING_FROM_ENV');

        (new LoadEnvironmentVariables)->bootstrap($this->app);

        $this->artisan('config:cache')->assertSuccessful();

        $config = require $this->app->getCachedConfigPath();

        $this->assertSame('from_production', $config['testconfig']['from_env']);
    }

    protected function setExternalEnvironmentVariable(string $key, string $value): void
    {
        putenv("{$key}={$value}");

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;

        // Ensure Env::get(...) uses a repository reflecting the updated environment.
        Env::enablePutenv();
    }

    protected function unsetExternalEnvironmentVariable(string $key): void
    {
        putenv($key);

        unset($_ENV[$key], $_SERVER[$key]);

        // The repository is immutable; rebuild it to reflect the cleared variable.
        Env::enablePutenv();
    }

    protected function resetLoadEnvironmentVariablesState(): void
    {
        $reflection = new \ReflectionClass(LoadEnvironmentVariables::class);
        $property = $reflection->getProperty('loadedEnvironmentVariables');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
