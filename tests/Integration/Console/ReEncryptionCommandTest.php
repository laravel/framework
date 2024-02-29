<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use function base64_decode;
use function base64_encode;
use function json_encode;
use function random_bytes;

class ReEncryptionCommandTest extends TestCase
{
    protected $appKey = '/JEsDQCLbuXaUjd/nz/cDcsoczyLX929uYxGuwIzEYs=';
    protected $newKey = 'A/XpDmqaahaIw7mmsJSg33NMVzsb1Bnj+7MYT4KmxhI=';
    protected $cipher = 'AES-256-CBC';

    protected function defineEnvironment($app)
    {
        $app['config']['database.default'] = 'testing';
        $app['config']['app.key'] = 'base64:' . $this->appKey;
        $app['config']['app.cipher'] = $this->cipher;
    }

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Schema::create('test_models', function (Blueprint $table) {
                $table->id();
                $table->text('foo');
                $table->text('bar');
                $table->text('baz');
                $table->text('qux');
                $table->text('fred')->nullable();
            });

            $encrypter = new Encrypter(base64_decode($this->appKey), $this->cipher);

            DB::table('test_models')->insert([
                'foo' => $encrypter->encrypt('foo', false),
                'bar' => $encrypter->encrypt(json_encode(['bar']), false),
                'baz' => 'verbatim',
                'qux' => $encrypter->encrypt(Json::encode(new Collection(['qux'])), false),
            ]);
        });

        parent::setUp();
    }

    public function testReEncryptsModel()
    {
        $this->artisan('crypt:re-encrypt', [
            'targets' => 'test_models:foo,bar,qux,fred',
            'key' => $this->newKey,
        ])
            ->expectsOutput('Using "...KmxhI=" key to re-encryption')
            ->assertExitCode(0);

        $encrypter = new Encrypter(base64_decode($this->newKey), $this->cipher);

        $row = DB::table('test_models')->where('id', 1)->first();

        $this->assertSame('foo', $encrypter->decrypt($row->foo, false));
        $this->assertSame('["bar"]', $encrypter->decrypt($row->bar, false));
        $this->assertSame('verbatim', $row->baz);
        $this->assertEquals(new Collection(['qux']), $encrypter->decrypt($row->qux, false));
    }

    public function testSkipsIfFailedDecryption()
    {
        $this->artisan('crypt:re-encrypt', [
            'targets' => 'test_models:foo,bar,baz,qux,fred',
            'key' => $this->newKey,
            '--skip-failed' => true
        ])
            ->assertExitCode(0);

        $encrypter = new Encrypter(base64_decode($this->newKey), $this->cipher);

        $row = DB::table('test_models')->where('id', 1)->first();

        $this->assertSame('foo', $encrypter->decrypt($row->foo, false));
        $this->assertSame('["bar"]', $encrypter->decrypt($row->bar, false));
        $this->assertSame('verbatim', $row->baz);
        $this->assertEquals(new Collection(['qux']), $encrypter->decrypt($row->qux, false));
    }
}
