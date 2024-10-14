<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class JobEncryptionTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('queue.default', 'database');
    }

    protected function tearDown(): void
    {
        JobEncryptionTestEncryptedJob::$ran = false;
        JobEncryptionTestNonEncryptedJob::$ran = false;

        parent::tearDown();
    }

    public function testEncryptedJobPayloadIsStoredEncrypted()
    {
        Bus::dispatch(new JobEncryptionTestEncryptedJob);

        $this->assertNotEmpty(
            decrypt(json_decode(DB::table('jobs')->first()->payload)->data->command)
        );
    }

    public function testNonEncryptedJobPayloadIsStoredRaw()
    {
        Bus::dispatch(new JobEncryptionTestNonEncryptedJob);

        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid');

        $this->assertInstanceOf(JobEncryptionTestNonEncryptedJob::class,
            unserialize(json_decode(DB::table('jobs')->first()->payload)->data->command)
        );

        decrypt(json_decode(DB::table('jobs')->first()->payload)->data->command);
    }

    public function testQueueCanProcessEncryptedJob()
    {
        Bus::dispatch(new JobEncryptionTestEncryptedJob);

        Queue::pop()->fire();

        $this->assertTrue(JobEncryptionTestEncryptedJob::$ran);
    }

    public function testQueueCanProcessUnEncryptedJob()
    {
        Bus::dispatch(new JobEncryptionTestNonEncryptedJob);

        Queue::pop()->fire();

        $this->assertTrue(JobEncryptionTestNonEncryptedJob::$ran);
    }
}

class JobEncryptionTestEncryptedJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class JobEncryptionTestNonEncryptedJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}
