<?php

namespace Illuminate\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ConfigWriter;
use PHPUnit\Framework\TestCase;

class ConfigWriterTest extends TestCase
{
    protected $tempDir;

    protected $files;

    protected $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->tempDir = sys_get_temp_dir().'/laravel-config-writer-test-'.uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->writer = new ConfigWriter($this->files);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testItCreatesNewFileWithSingleKey(): void
    {
        $path = $this->tempDir.'/services.php';

        $this->writer->write($path, ['key'], 'STRIPE_KEY', 'null');

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringContainsString("'key'", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesNewFileWithNestedKeys(): void
    {
        $path = $this->tempDir.'/services.php';

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("'stripe'", $contents);
        $this->assertStringContainsString("'key'", $contents);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesNewFileWithDeeplyNestedKeys(): void
    {
        $path = $this->tempDir.'/services.php';

        $this->writer->write($path, ['stripe', 'webhook', 'secret'], 'STRIPE_WEBHOOK_SECRET');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("'stripe'", $contents);
        $this->assertStringContainsString("'webhook'", $contents);
        $this->assertStringContainsString("'secret'", $contents);
        $this->assertStringContainsString("env('STRIPE_WEBHOOK_SECRET')", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesNewFileWithStringDefault(): void
    {
        $path = $this->tempDir.'/app.php';

        $this->writer->write($path, ['timezone'], 'APP_TIMEZONE', 'UTC');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('APP_TIMEZONE', 'UTC')", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesNewFileWithNoDefault(): void
    {
        $path = $this->tempDir.'/services.php';

        $this->writer->write($path, ['key'], 'STRIPE_KEY');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY')", $contents);
        $this->assertStringNotContainsString("env('STRIPE_KEY',", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesNewFileWithNullDefault(): void
    {
        $path = $this->tempDir.'/services.php';

        $this->writer->write($path, ['key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesNewFileWithTrueDefault(): void
    {
        $path = $this->tempDir.'/app.php';

        $this->writer->write($path, ['debug'], 'APP_DEBUG', 'true');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('APP_DEBUG', true)", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesNewFileWithFalseDefault(): void
    {
        $path = $this->tempDir.'/app.php';

        $this->writer->write($path, ['debug'], 'APP_DEBUG', 'false');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('APP_DEBUG', false)", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesParentDirectories(): void
    {
        $path = $this->tempDir.'/nested/dir/services.php';

        $this->writer->write($path, ['key'], 'STRIPE_KEY');

        $this->assertFileExists($path);
        $this->assertValidPhp(file_get_contents($path));
    }

    public function testItAddsKeyToExistingFile(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
    ],
];
PHP);

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('MAILGUN_DOMAIN')", $contents);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringContainsString("'mailgun'", $contents);
        $this->assertStringContainsString("'stripe'", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItReplacesExistingValue(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return [
    'stripe' => [
        'key' => 'hardcoded_value',
    ],
];
PHP);

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringNotContainsString('hardcoded_value', $contents);
        $this->assertValidPhp($contents);
    }

    public function testItReplacesExistingEnvCall(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return [
    'stripe' => [
        'key' => env('OLD_KEY', 'old_default'),
    ],
];
PHP);

        $this->writer->write($path, ['stripe', 'key'], 'NEW_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('NEW_KEY', null)", $contents);
        $this->assertStringNotContainsString('OLD_KEY', $contents);
        $this->assertValidPhp($contents);
    }

    public function testItPreservesFormatting(): void
    {
        $path = $this->tempDir.'/services.php';
        $original = <<<'PHP'
<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],
];
PHP;
        file_put_contents($path, $original);

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        // The original mailgun section should remain intact
        $this->assertStringContainsString("'mailgun' => [\n        'domain' => env('MAILGUN_DOMAIN'),\n        'secret' => env('MAILGUN_SECRET'),\n    ]", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItAddsToExistingNestedArray(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
    ],
];
PHP);

        $this->writer->write($path, ['stripe', 'secret'], 'STRIPE_SECRET', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY')", $contents);
        $this->assertStringContainsString("env('STRIPE_SECRET', null)", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItCreatesIntermediateArrays(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
    ],
];
PHP);

        $this->writer->write($path, ['stripe', 'webhook', 'secret'], 'STRIPE_WEBHOOK_SECRET');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("'stripe'", $contents);
        $this->assertStringContainsString("'webhook'", $contents);
        $this->assertStringContainsString("'secret'", $contents);
        $this->assertStringContainsString("env('STRIPE_WEBHOOK_SECRET')", $contents);
        $this->assertStringContainsString("env('MAILGUN_DOMAIN')", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItDoesNothingWhenFileHasNoReturnArray(): void
    {
        $path = $this->tempDir.'/broken.php';
        $original = <<<'PHP'
<?php

echo 'hello';
PHP;
        file_put_contents($path, $original);

        $this->writer->write($path, ['key'], 'SOME_KEY');

        $this->assertSame($original, file_get_contents($path));
    }

    public function testItHandlesEmptyReturnArray(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return [
];
PHP);

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItHandlesFileWithComments(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

// Third-party service configuration.
return [
    // Mailgun settings
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
    ],
];
PHP);

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString('// Third-party service configuration.', $contents);
        $this->assertStringContainsString('// Mailgun settings', $contents);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItProducesValidPhpForNewFileWithAllDefaultTypes(): void
    {
        $cases = [
            ['null', "env('K', null)"],
            ['true', "env('K', true)"],
            ['false', "env('K', false)"],
            ['some_string', "env('K', 'some_string')"],
            ['', "env('K')"],
        ];

        foreach ($cases as $i => [$default, $expected]) {
            $path = $this->tempDir."/test_{$i}.php";
            $this->writer->write($path, ['key'], 'K', $default);

            $contents = file_get_contents($path);
            $this->assertStringContainsString($expected, $contents, "Failed for default: '{$default}'");
            $this->assertValidPhp($contents);
        }
    }

    public function testItHandlesMultipleWritesToSameFile(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return [
];
PHP);

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');
        $this->writer->write($path, ['stripe', 'secret'], 'STRIPE_SECRET', 'null');
        $this->writer->write($path, ['mailgun', 'domain'], 'MAILGUN_DOMAIN');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringContainsString("env('STRIPE_SECRET', null)", $contents);
        $this->assertStringContainsString("env('MAILGUN_DOMAIN')", $contents);
        $this->assertValidPhp($contents);
    }

    public function testItHandlesOldArraySyntax(): void
    {
        $path = $this->tempDir.'/services.php';
        file_put_contents($path, <<<'PHP'
<?php

return array(
    'mailgun' => array(
        'domain' => env('MAILGUN_DOMAIN'),
    ),
);
PHP);

        $this->writer->write($path, ['stripe', 'key'], 'STRIPE_KEY', 'null');

        $contents = file_get_contents($path);
        $this->assertStringContainsString("env('STRIPE_KEY', null)", $contents);
        $this->assertStringContainsString("env('MAILGUN_DOMAIN')", $contents);
        $this->assertValidPhp($contents);
    }

    protected function assertValidPhp(string $code): void
    {
        $result = exec('echo '.escapeshellarg($code).' | php -l 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'PHP syntax check failed: '.implode("\n", $output));
    }
}
