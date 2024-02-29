<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Schema\Blueprint;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class CryptRefreshCommandTest extends TestCase
{
    protected $appKey = '/JEsDQCLbuXaUjd/nz/cDcsoczyLX929uYxGuwIzEYs=';
    protected $oldKey = 'A/XpDmqaahaIw7mmsJSg33NMVzsb1Bnj+7MYT4KmxhI=';
    protected $cipher = 'AES-256-CBC';

    protected function defineEnvironment($app)
    {
        $app['config']['database.default'] = 'testing';
        $app['config']['app.key'] = 'base64:' . $this->appKey;
        $app['config']['app.previous_keys'] = ['base64:' . $this->oldKey];
        $app['config']['app.cipher'] = $this->cipher;
    }

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->app->make('db.schema')->create('test_models', function (Blueprint $table) {
                $table->id();
                $table->text('foo');
                $table->text('bar');
                $table->text('baz')->nullable();
            });

            $this->app->make('db')->table('test_models')->insert([
                'foo' => 'eyJpdiI6ImZJQnhqVGc5QmRDL1VIOHU3R1Z0S2c9PSIsInZhbHVlIjoiNDYvRk9CWW1hOGFDaGZyMklaOWxidz09IiwibWFjIjoiZjcwMTE0MDVmY2E4MmQ4YjU3MDYwZWIzYTdhZjJlNWE5YjM2YTZjODRkOWM2MmVmZDNmNDlkZDAzMWViNmMwOCIsInRhZyI6IiJ9',
                'bar' => 'bar',
            ]);
        });

        parent::setUp();
    }

    public function testRefreshesEncryption()
    {
        $this->artisan('crypt:refresh', ['targets' => 'test_models:foo,baz',])
            ->expectsOutput('Using laravel_refreshed_at as flag column in test_models to refresh rows.')
            ->assertExitCode(0);

        $row = $this->app->make('db')->table('test_models')->where('id', 1)->first();

        $encrypter = $this->app->make('encrypter');

        $this->assertSame('foo', $encrypter->decrypt($row->foo, false));
        $this->assertSame('bar', $row->bar);
        $this->assertNull($row->baz);
        $this->assertObjectNotHasProperty('refreshed_at', $row);

        $this->assertFalse($this->app->make('db.schema')->hasColumn('test_models', 'laravel_refreshed_at'));
    }

    public function testRefreshesEncryptionWithCustomFlagColumn()
    {
        $this->artisan('crypt:refresh', [
                'targets' => 'test_models:foo,baz',
                '--flag-column' => 'custom_refreshed_at'
            ])
            ->expectsOutput('Using custom_refreshed_at as flag column in test_models to refresh rows.')
            ->assertExitCode(0);

        $row = $this->app->make('db')->table('test_models')->where('id', 1)->first();

        $encrypter = $this->app->make('encrypter');

        $this->assertSame('foo', $encrypter->decrypt($row->foo, false));
        $this->assertSame('bar', $row->bar);
        $this->assertNull($row->baz);
        $this->assertObjectNotHasProperty('custom_refreshed_at', $row);

        $this->assertFalse($this->app->make('db.schema')->hasColumn('test_models', 'custom_refreshed_at'));
        $this->assertFalse($this->app->make('db.schema')->hasColumn('test_models', 'laravel_refreshed_at'));
    }

    public function testDoesntRefreshesAlreadyRefreshedRow()
    {
        $this->app->make('db.schema')->table('test_models', fn($table) => $table->timestamp('laravel_refreshed_at')->nullable());
        $this->app->make('db')->table('test_models')
            ->where('id', 1)
            ->update([
                'foo' => 'untouched',
                'bar' => 'untouched',
                'baz' => null,
                'laravel_refreshed_at' => now(),
            ]);

        $this->artisan('crypt:refresh', ['targets' => 'test_models:foo,baz'])->assertExitCode(0);

        $row = $this->app->make('db')->table('test_models')->where('id', 1)->first();

        $this->assertSame('untouched', $row->foo);
        $this->assertSame('untouched', $row->bar);
        $this->assertNull($row->baz);
    }

    public function testRefreshesAllColumnsWhenFlagColumnDisabled()
    {
        $this->app->make('db.schema')->table('test_models', fn($table) => $table->timestamp('laravel_refreshed_at')->nullable());
        $this->app->make('db')->table('test_models')
            ->where('id', 1)
            ->update([
                'laravel_refreshed_at' => now(),
            ]);

        $this->artisan('crypt:refresh', [
            'targets' => 'test_models:foo,baz',
            '--flag-column' => null
        ])
            ->expectsOutput('No flag column was issued to skip already refreshed rows.')
            ->assertExitCode(0);

        $row = $this->app->make('db')->table('test_models')->where('id', 1)->first();

        $this->assertNotSame('untouched', $row->foo);
        $this->assertNotSame('untouched', $row->bar);
        $this->assertNull($row->baz);
    }

    public function testDoesntRefreshesAlreadyRefreshedRowWithCustomFlagColumn()
    {
        $this->app->make('db.schema')->table('test_models', fn($table) => $table->timestamp('custom_at')->nullable());
        $this->app->make('db')->table('test_models')
            ->where('id', 1)
            ->update([
                'foo' => 'untouched',
                'bar' => 'untouched',
                'baz' => null,
                'custom_at' => now(),
            ]);

        $this->artisan('crypt:refresh', [
            'targets' => 'test_models:foo,baz',
            '--flag-column' => 'custom_at'
        ])->assertExitCode(0);

        $row = $this->app->make('db')->table('test_models')->where('id', 1)->first();

        $this->assertSame('untouched', $row->foo);
        $this->assertSame('untouched', $row->bar);
        $this->assertNull($row->baz);
        $this->assertObjectNotHasProperty('custom_at', $row);

        $this->assertFalse($this->app->make('db.schema')->hasColumn('test_models', 'custom_at'));
        $this->assertFalse($this->app->make('db.schema')->hasColumn('test_models', 'laravel_refreshed_at'));
    }

    public function testFailsWhenEmptyTable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No table name was issued.');

        $this->artisan('crypt:refresh', [
            'targets' => '',
        ]);
    }

    public function testFailsWhenEmptyColumns()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No columns were issued.');

        $this->artisan('crypt:refresh', [
            'targets' => 'table:     ',
        ]);
    }

    public function testFailsWhenColumnIsNotEncrypted()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid.');

        $this->artisan('crypt:refresh', [
            'targets' => 'test_models:bar',
        ]);
    }
}
