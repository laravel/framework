<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Tests\Integration\Generators\TestCase;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use LogicException;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ConfigCacheCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;
    use VerifiesDoubles;

    protected $files = [
        'bootstrap/cache/config.php',
        'config/testconfig.php',
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

    public function testConfigurationIsCachedAtomically()
    {
        $files = Double::for(Filesystem::class)->passthru(new Filesystem);

        $this->app->instance('files', $files);

        $this->artisan('config:cache')->assertSuccessful();

        $this->assertFileExists($this->app->getCachedConfigPath());

        $files->received('replace')->times(1)->with($this->app->getCachedConfigPath(), Argument::type('string'));
        $files->received('put')->never();
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

        $this->expectExceptionObject(new LogicException('Your configuration files could not be serialized because the value at "testconfig.closure" is non-serializable.'));

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

        $this->expectExceptionObject(new LogicException('Your configuration files could not be serialized because the value at "testconfig.nested.deep.closure" is non-serializable.'));

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
}
