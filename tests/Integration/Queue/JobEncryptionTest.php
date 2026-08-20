<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Tests\App\Jobs\EncryptedJob;
use Illuminate\Tests\App\Jobs\NonEncryptedJob;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class JobEncryptionTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('queue.default', 'database');
    }

    protected function tearDown(): void
    {
        EncryptedJob::$ran = false;
        NonEncryptedJob::$ran = false;

        parent::tearDown();
    }

    public function testEncryptedJobPayloadIsStoredEncrypted()
    {
        Bus::dispatch(new EncryptedJob);

        $this->assertNotEmpty(
            decrypt(json_decode(DB::table('jobs')->first()->payload)->data->command)
        );
    }

    public function testNonEncryptedJobPayloadIsStoredRaw()
    {
        Bus::dispatch(new NonEncryptedJob);

        $this->expectExceptionObject(new DecryptException('The payload is invalid'));

        $this->assertInstanceOf(NonEncryptedJob::class,
            unserialize(json_decode(DB::table('jobs')->first()->payload)->data->command)
        );

        decrypt(json_decode(DB::table('jobs')->first()->payload)->data->command);
    }

    public function testQueueCanProcessEncryptedJob()
    {
        Bus::dispatch(new EncryptedJob);

        Queue::pop()->fire();

        $this->assertTrue(EncryptedJob::$ran);
    }

    public function testQueueCanProcessUnEncryptedJob()
    {
        Bus::dispatch(new NonEncryptedJob);

        Queue::pop()->fire();

        $this->assertTrue(NonEncryptedJob::$ran);
    }
}
