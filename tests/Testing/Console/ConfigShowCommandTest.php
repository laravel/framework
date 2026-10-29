<?php

namespace Illuminate\Tests\Testing\Console;

use Illuminate\Foundation\Console\ConfigShowCommand;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ConfigShowCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('COLUMNS=64');
    }

    public function testDisplayConfig()
    {
        config()->set('test', [
            'string' => 'Test',
            'int' => 1,
            'float' => 1.2,
            'boolean' => true,
            'null' => null,
            'array' => [
                ConfigShowCommand::class,
            ],
            'empty_array' => [],
            'assoc_array' => ['foo' => 'bar'],
            'class' => new \stdClass,
        ]);

        $this->artisan(ConfigShowCommand::class, ['config' => 'test'])
            ->assertSuccessful()
            ->expectsOutput('  test .......................................................  ')
            ->expectsOutput('  string ................................................ Test  ')
            ->expectsOutput('  int ...................................................... 1  ')
            ->expectsOutput('  float .................................................. 1.2  ')
            ->expectsOutput('  boolean ............................................... true  ')
            ->expectsOutput('  null .................................................. null  ')
            ->expectsOutput('  array ⇁ 0 .. Illuminate\Foundation\Console\ConfigShowCommand  ')
            ->expectsOutput('  empty_array ............................................. []  ')
            ->expectsOutput('  assoc_array ⇁ foo ...................................... bar  ')
            ->expectsOutput('  class ............................................. stdClass  ');
    }

    public function testDisplayNestedConfigItems()
    {
        config()->set('test', [
            'nested' => [
                'foo' => 'bar',
            ],
        ]);

        $this->artisan(ConfigShowCommand::class, ['config' => 'test.nested'])
            ->assertSuccessful()
            ->expectsOutput('  test.nested ................................................  ')
            ->expectsOutput('  foo .................................................... bar  ');
    }

    public function testDisplaySingleValue()
    {
        config()->set('foo', 'bar');

        $this->artisan(ConfigShowCommand::class, ['config' => 'foo'])
            ->assertSuccessful()
            ->expectsOutput('  foo .................................................... bar  ');
    }

    public function testMasksSensitiveValues()
    {
        config()->set('test', [
            'password' => 'hunter2',
            'api_key' => 'sk-hunter2',
            'previous_keys' => ['base64:hunter2'],
            'url' => 'mysql://root:hunter2@localhost/app',
            'token' => null,
            'secret' => '',
            'host' => 'localhost',
            'password_timeout' => 10800,
        ]);

        $this->artisan(ConfigShowCommand::class, ['config' => 'test'])
            ->assertSuccessful()
            ->expectsOutput('  password .......................................... ********  ')
            ->expectsOutput('  api_key ........................................... ********  ')
            ->expectsOutput('  previous_keys ⇁ 0 ................................. ********  ')
            ->expectsOutput('  url .................... mysql://root:********@localhost/app  ')
            ->expectsOutput('  token ................................................. null  ')
            ->expectsOutput('  secret .....................................................  ')
            ->expectsOutput('  host ............................................. localhost  ')
            ->expectsOutput('  password_timeout ..................................... 10800  ')
            ->doesntExpectOutputToContain('hunter2');
    }

    #[DataProvider('sensitiveConfig')]
    public function testMasksSensitiveConfig($config, $key, $secret, $masked)
    {
        config()->set('test', $config);

        $this->artisan(ConfigShowCommand::class, ['config' => $key])
            ->assertSuccessful()
            ->expectsOutputToContain($masked)
            ->doesntExpectOutputToContain($secret);
    }

    public static function sensitiveConfig()
    {
        return [
            'camel case key' => [['apiKey' => 'hunter2'], 'test', 'hunter2', '********'],
            'camel case secret' => [['clientSecret' => 'hunter2'], 'test', 'hunter2', '********'],
            'passphrase' => [['passphrase' => 'hunter2'], 'test', 'hunter2', '********'],
            'token' => [['token' => 'hunter2'], 'test', 'hunter2', '********'],
            'credentials' => [['credentials' => 'hunter2'], 'test', 'hunter2', '********'],
            'private' => [['private' => 'hunter2'], 'test', 'hunter2', '********'],
            'dsn' => [['dsn' => 'https://hunter2@sentry.example.com/1'], 'test', 'hunter2', '********'],
            'sasl' => [['sasl' => ['user', 'hunter2']], 'test', 'hunter2', '********'],
            'list shown directly' => [['previous_keys' => ['hunter2']], 'test.previous_keys', 'hunter2', '********'],
            'url password of zero' => [['url' => 'mysql://root:0@localhost/app'], 'test', ':0@', 'mysql://root:********@localhost/app'],
            'url password in query' => [['url' => 'redis://localhost:6379?password=hunter2'], 'test', 'hunter2', 'redis://localhost:6379?password=********'],
        ];
    }

    public function testMasksSensitiveSingleValue()
    {
        config()->set('test.secret', 'hunter2');

        $this->artisan(ConfigShowCommand::class, ['config' => 'test.secret'])
            ->assertSuccessful()
            ->expectsOutput('  test.secret ....................................... ********  ');
    }

    public function testRevealsSensitiveValuesWhenRequested()
    {
        config()->set('test', [
            'password' => 'hunter2',
            'url' => 'mysql://root:hunter2@localhost/app',
        ]);

        $this->artisan(ConfigShowCommand::class, ['config' => 'test', '--show-sensitive' => true])
            ->assertSuccessful()
            ->expectsOutput('  password ........................................... hunter2  ')
            ->expectsOutput('  url ..................... mysql://root:hunter2@localhost/app  ');
    }

    public function testDisplayErrorIfConfigDoesNotExist()
    {
        $this->artisan(ConfigShowCommand::class, ['config' => 'invalid'])
            ->assertFailed();
    }
}
