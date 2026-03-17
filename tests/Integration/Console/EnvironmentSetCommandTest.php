<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase;

class EnvironmentSetCommandTest extends TestCase
{
    protected $tempDir;

    protected $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->tempDir = sys_get_temp_dir().'/laravel-env-set-test-'.uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir.'/config', 0755, true);

        $this->app->useEnvironmentPath($this->tempDir);
        $this->app->setBasePath($this->tempDir);

        file_put_contents($this->tempDir.'/.env', '');
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testItSetsANewEnvironmentVariable(): void
    {
        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'sk_test_123', '-n' => true])
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] set successfully.')
            ->assertExitCode(0);

        $this->assertStringContainsString('STRIPE_KEY=sk_test_123', file_get_contents($this->tempDir.'/.env'));
    }

    public function testItSetsVariableWithInlineValue(): void
    {
        $this->artisan('env:set', ['key' => 'STRIPE_KEY=sk_test_123', '-n' => true])
            ->expectsOutputToContain('Using value from argument: sk_test_123')
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] set successfully.')
            ->assertExitCode(0);

        $this->assertStringContainsString('STRIPE_KEY=sk_test_123', file_get_contents($this->tempDir.'/.env'));
    }

    public function testItSetsVariableWithQuotedInlineValue(): void
    {
        $this->artisan('env:set', ['key' => 'APP_NAME="My App"', '-n' => true])
            ->expectsOutputToContain('Using value from argument: My App')
            ->assertExitCode(0);

        $this->assertStringContainsString('APP_NAME="My App"', file_get_contents($this->tempDir.'/.env'));
    }

    public function testItSetsVariableWithSingleQuotedInlineValue(): void
    {
        $this->artisan('env:set', ['key' => "APP_NAME='My App'", '-n' => true])
            ->expectsOutputToContain('Using value from argument: My App')
            ->assertExitCode(0);

        $this->assertStringContainsString('APP_NAME="My App"', file_get_contents($this->tempDir.'/.env'));
    }

    public function testItFailsWhenEnvFileIsMissing(): void
    {
        unlink($this->tempDir.'/.env');

        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'sk_test_123'])
            ->expectsOutputToContain('Environment file not found.')
            ->assertExitCode(1);
    }

    public function testItPromptsForOverwriteWhenVariableExists(): void
    {
        file_put_contents($this->tempDir.'/.env', 'STRIPE_KEY=old_value');
        file_put_contents($this->tempDir.'/.env.example', '');

        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'new_value'])
            ->expectsConfirmation('Environment variable [STRIPE_KEY] already exists. Overwrite?', 'yes')
            ->expectsConfirmation('Add [STRIPE_KEY] to .env.example?', 'no')
            ->expectsQuestion('What config key should this be associated with? (Optional)', '')
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] set successfully.')
            ->assertExitCode(0);

        $this->assertStringContainsString('STRIPE_KEY=new_value', file_get_contents($this->tempDir.'/.env'));
    }

    public function testItDoesNotOverwriteWhenUserDeclinesConfirmation(): void
    {
        file_put_contents($this->tempDir.'/.env', 'STRIPE_KEY=old_value');

        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'new_value'])
            ->expectsConfirmation('Environment variable [STRIPE_KEY] already exists. Overwrite?', 'no')
            ->assertExitCode(0);

        $this->assertStringContainsString('STRIPE_KEY=old_value', file_get_contents($this->tempDir.'/.env'));
    }

    public function testItOverwritesWithForceOption(): void
    {
        file_put_contents($this->tempDir.'/.env', 'STRIPE_KEY=old_value');
        file_put_contents($this->tempDir.'/.env.example', '');

        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'new_value', '--force' => true])
            ->expectsConfirmation('Add [STRIPE_KEY] to .env.example?', 'no')
            ->expectsQuestion('What config key should this be associated with? (Optional)', '')
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] set successfully.')
            ->assertExitCode(0);

        $this->assertStringContainsString('STRIPE_KEY=new_value', file_get_contents($this->tempDir.'/.env'));
    }

    public function testItFailsInNonInteractiveModeWhenVariableExistsWithoutForce(): void
    {
        file_put_contents($this->tempDir.'/.env', 'STRIPE_KEY=old_value');

        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'new_value', '-n' => true])
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] already exists. Use --force to overwrite.')
            ->assertExitCode(1);
    }

    public function testItAddsToEnvExample(): void
    {
        file_put_contents($this->tempDir.'/.env.example', '');

        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'sk_test_123', '--example' => true, '-n' => true])
            ->expectsOutputToContain('Added [STRIPE_KEY] to .env.example.')
            ->assertExitCode(0);

        $this->assertStringContainsString('STRIPE_KEY=', file_get_contents($this->tempDir.'/.env.example'));
    }

    public function testItDoesNotAddToEnvExampleInNonInteractiveModeWithoutFlag(): void
    {
        file_put_contents($this->tempDir.'/.env.example', '');

        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'sk_test_123', '-n' => true])
            ->assertExitCode(0);

        $this->assertStringNotContainsString('STRIPE_KEY', file_get_contents($this->tempDir.'/.env.example'));
    }

    public function testItSkipsEnvExampleWhenFileDoesNotExist(): void
    {
        $this->artisan('env:set', ['key' => 'STRIPE_KEY', '--value' => 'sk_test_123', '-n' => true])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempDir.'/.env.example');
    }

    public function testItWritesConfigForNewFile(): void
    {
        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '--config-key' => 'services.stripe.key',
            '--default' => 'null',
            '-n' => true,
        ])
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] set successfully.')
            ->expectsOutputToContain("Config [services.stripe.key] set to env('STRIPE_KEY').")
            ->assertExitCode(0);

        $configFile = $this->tempDir.'/config/services.php';
        $this->assertFileExists($configFile);

        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringContainsString("'stripe'", $contents);
        $this->assertStringContainsString("'key'", $contents);
    }

    public function testItWritesConfigForExistingFile(): void
    {
        $configFile = $this->tempDir.'/config/services.php';
        file_put_contents($configFile, <<<'PHP'
<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
    ],
];
PHP);

        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '--config-key' => 'services.stripe.key',
            '--default' => 'null',
            '--force' => true,
            '-n' => true,
        ])
            ->assertExitCode(0);

        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringContainsString("'mailgun'", $contents);
        $this->assertStringContainsString("env('MAILGUN_DOMAIN')", $contents);
    }

    public function testItWritesConfigWithStringDefault(): void
    {
        $this->artisan('env:set', [
            'key' => 'APP_TIMEZONE',
            '--value' => 'America/New_York',
            '--config-key' => 'app.timezone',
            '--default' => 'UTC',
            '-n' => true,
        ])
            ->assertExitCode(0);

        $configFile = $this->tempDir.'/config/app.php';
        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("env('APP_TIMEZONE', 'UTC')", $contents);
    }

    public function testItWritesConfigWithEmptyDefault(): void
    {
        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '--config-key' => 'services.stripe.key',
            '--default' => '',
            '-n' => true,
        ])
            ->assertExitCode(0);

        $configFile = $this->tempDir.'/config/services.php';
        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("env('STRIPE_KEY')", $contents);
        $this->assertStringNotContainsString("env('STRIPE_KEY',", $contents);
    }

    public function testItFailsWithConfigKeyMissingNestedKey(): void
    {
        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '--config-key' => 'services',
            '-n' => true,
        ])
            ->expectsOutputToContain('Config key must include at least a file and a key')
            ->assertExitCode(1);
    }

    public function testItHandlesFullNonInteractiveMode(): void
    {
        file_put_contents($this->tempDir.'/.env.example', '');

        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '--config-key' => 'services.stripe.key',
            '--default' => 'null',
            '--example' => true,
            '--force' => true,
            '-n' => true,
        ])
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] set successfully.')
            ->expectsOutputToContain("Config [services.stripe.key] set to env('STRIPE_KEY').")
            ->assertExitCode(0);

        $this->assertStringContainsString('STRIPE_KEY=sk_test_123', file_get_contents($this->tempDir.'/.env'));
        $this->assertStringContainsString('STRIPE_KEY=', file_get_contents($this->tempDir.'/.env.example'));
        $this->assertFileExists($this->tempDir.'/config/services.php');
    }

    public function testItSkipsConfigInNonInteractiveModeWithoutConfigKey(): void
    {
        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '-n' => true,
        ])
            ->expectsOutputToContain('Environment variable [STRIPE_KEY] set successfully.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempDir.'/config/services.php');
    }

    public function testItSkipsEnvExampleInNonInteractiveModeWithoutExampleFlag(): void
    {
        file_put_contents($this->tempDir.'/.env.example', '');

        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '-n' => true,
        ])
            ->assertExitCode(0);

        $this->assertStringNotContainsString('STRIPE_KEY', file_get_contents($this->tempDir.'/.env.example'));
    }

    public function testItHandlesDeeplyNestedConfigKeys(): void
    {
        $this->artisan('env:set', [
            'key' => 'STRIPE_WEBHOOK_SECRET',
            '--value' => 'whsec_test',
            '--config-key' => 'services.stripe.webhook.secret',
            '--default' => '',
            '-n' => true,
        ])
            ->assertExitCode(0);

        $configFile = $this->tempDir.'/config/services.php';
        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("'stripe'", $contents);
        $this->assertStringContainsString("'webhook'", $contents);
        $this->assertStringContainsString("'secret'", $contents);
        $this->assertStringContainsString("env('STRIPE_WEBHOOK_SECRET')", $contents);
    }

    public function testItReplacesExistingConfigValue(): void
    {
        $configFile = $this->tempDir.'/config/services.php';
        file_put_contents($configFile, <<<'PHP'
<?php

return [
    'stripe' => [
        'key' => 'hardcoded_value',
    ],
];
PHP);

        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '--config-key' => 'services.stripe.key',
            '--default' => 'null',
            '--force' => true,
            '-n' => true,
        ])
            ->assertExitCode(0);

        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringNotContainsString('hardcoded_value', $contents);
    }

    public function testItWritesConfigWithBooleanDefault(): void
    {
        $this->artisan('env:set', [
            'key' => 'APP_DEBUG',
            '--value' => 'true',
            '--config-key' => 'app.debug',
            '--default' => 'true',
            '-n' => true,
        ])
            ->assertExitCode(0);

        $configFile = $this->tempDir.'/config/app.php';
        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("env('APP_DEBUG', true)", $contents);
    }

    public function testItWritesConfigWithFalseDefault(): void
    {
        $this->artisan('env:set', [
            'key' => 'APP_DEBUG',
            '--value' => 'false',
            '--config-key' => 'app.debug',
            '--default' => 'false',
            '-n' => true,
        ])
            ->assertExitCode(0);

        $configFile = $this->tempDir.'/config/app.php';
        $contents = file_get_contents($configFile);
        $this->assertStringContainsString("env('APP_DEBUG', false)", $contents);
    }

    public function testItPreservesExistingEnvVariablesWhenAddingNew(): void
    {
        file_put_contents($this->tempDir.'/.env', "APP_NAME=Laravel\nAPP_ENV=local");

        $this->artisan('env:set', [
            'key' => 'STRIPE_KEY',
            '--value' => 'sk_test_123',
            '-n' => true,
        ])
            ->assertExitCode(0);

        $contents = file_get_contents($this->tempDir.'/.env');
        $this->assertStringContainsString('APP_NAME=Laravel', $contents);
        $this->assertStringContainsString('APP_ENV=local', $contents);
        $this->assertStringContainsString('STRIPE_KEY=sk_test_123', $contents);
    }
}
