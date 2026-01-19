<?php

namespace Foundation\Console;

use Generator;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class KeyGenerateCommandTest extends TestCase
{
    private string $testEnvFileName = '';

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->testEnvFileName)) {
            unlink($this->testEnvFileName);
        }
    }

    #[DataProvider('keyNamesDataProvider')]
    public function test_it_can_generate_keys_with_provided_name(
        ?string $optionValue,
        string $expectedEnvVariable,
    ): void {
        // Arrange

        // Use a unique filename to avoid flakiness with other tests in parallel mode.
        $this->testEnvFileName = uniqid().'.env';

        $envPath = base_path($this->testEnvFileName);

        $envFileContent = 'NAMESPACE.APP_ENV=local'.PHP_EOL
            .'APP_DEBUG=false'.PHP_EOL;

        $appKeyLine = sprintf('%s=', $optionValue ?? 'APP_KEY');

        file_put_contents($envPath, $envFileContent.$appKeyLine);

        $this->app['config']->set('app.key', '');

        $this->app->useEnvironmentPath(base_path());
        $this->app->loadEnvironmentFrom($this->testEnvFileName);

        // Act

        $this->artisan(KeyGenerateCommand::class, $optionValue ? ['--name' => $optionValue] : [])
            ->expectsOutputToContain('Application key set successfully.')
            ->assertExitCode(0);

        // Assert

        $content = file_get_contents($envPath);

        $expectedSubString = $envFileContent."{$expectedEnvVariable}=base64:";

        $this->assertStringContainsString($expectedEnvVariable, $content);
    }

    public static function keyNamesDataProvider(): Generator
    {
        yield 'implicit default key' => [
            'optionValue' => null,
            'expectedEnvVariable' => 'APP_KEY',
        ];

        yield 'explicit default key' => [
            'optionValue' => 'APP_KEY',
            'expectedEnvVariable' => 'APP_KEY',
        ];

        yield 'custom key' => [
            'optionValue' => 'MY_SPECIAL_APP_KEY',
            'expectedEnvVariable' => 'MY_SPECIAL_APP_KEY',
        ];

        yield 'custom key with regex char' => [
            'optionValue' => 'NAMESPACE.APP_KEY',
            'expectedEnvVariable' => 'NAMESPACE.APP_KEY',
        ];

        yield 'custom key with regex char 2' => [
            'optionValue' => 'NAMESPACE$APP_KEY',
            'expectedEnvVariable' => 'NAMESPACE$APP_KEY',
        ];
    }
}
