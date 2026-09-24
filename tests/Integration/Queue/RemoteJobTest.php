<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldRunRemotely;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Queue as OnQueue;
use Illuminate\Queue\Attributes\RemoteName;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithRemoteWorker;
use Illuminate\Queue\RemoteJobFailed;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use LogicException;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class RemoteJobTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    public static $result;

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('queue.default', 'database');
    }

    protected function tearDown(): void
    {
        static::$result = null;

        parent::tearDown();
    }

    public function testPayloadIsPlainJson()
    {
        ResizeImage::dispatch('s3://bucket/cat.jpg', 800);

        $payload = $this->payload();

        $this->assertSame('resize_image', $payload['job']);
        $this->assertSame(ResizeImage::class, $payload['displayName']);
        $this->assertSame(['path' => 's3://bucket/cat.jpg', 'width' => 800], $payload['data']);
        $this->assertSame(3, $payload['maxTries']);
        $this->assertSame('5,30', $payload['backoff']);
        $this->assertSame('images', DB::table('jobs')->first()->queue);
        $this->assertArrayNotHasKey('reply', $payload);
    }

    public function testNameAndDataMayBeCustomized()
    {
        TranscodeVideo::dispatch(42);

        $payload = $this->payload();

        $this->assertSame('videos.transcode', $payload['job']);
        $this->assertSame(['video' => ['id' => 42]], $payload['data']);
    }

    public function testContextTravelsWithThePayload()
    {
        Context::add('tenant', 'acme');

        ResizeImage::dispatch('s3://bucket/cat.jpg', 800);

        $this->assertArrayHasKey('illuminate:log:context', $this->payload());
    }

    public function testThenCallbackRunsWhenRemoteWorkerCompletes()
    {
        ResizeImage::dispatch('s3://bucket/cat.jpg', 800)
            ->then(fn (array $result) => RemoteJobTest::$result = $result['url']);

        $reply = $this->payload()['reply'];

        $this->assertSame('default', $reply['queue']);

        $this->replyAsRemoteWorker($reply, ['status' => 'completed', 'result' => ['url' => 's3://bucket/cat-800.jpg']]);

        $this->assertSame('s3://bucket/cat-800.jpg', static::$result);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function testCatchCallbackRunsWhenRemoteWorkerFails()
    {
        ResizeImage::dispatch('s3://bucket/cat.jpg', 800)
            ->replyOn('callbacks')
            ->catch(fn (RemoteJobFailed $e) => RemoteJobTest::$result = [$e->job, $e->getMessage(), $e->type]);

        $reply = $this->payload()['reply'];

        $this->assertSame('callbacks', $reply['queue']);

        $this->replyAsRemoteWorker($reply, ['status' => 'failed', 'error' => ['message' => 'Unsupported format', 'type' => 'ValueError']]);

        $this->assertSame(['resize_image', 'Unsupported format', 'ValueError'], static::$result);
    }

    public function testTamperedReplyTokenIsRejected()
    {
        ResizeImage::dispatch('s3://bucket/cat.jpg', 800)->then(fn () => RemoteJobTest::$result = 'ran');

        $reply = $this->payload()['reply'];
        $reply['token'] = base64_encode('{"iv":"x","value":"y","mac":"z"}');

        $this->expectException(DecryptException::class);

        try {
            $this->replyAsRemoteWorker($reply, ['status' => 'completed', 'result' => []]);
        } finally {
            $this->assertNull(static::$result);
        }
    }

    public function testRemoteJobsCannotBeDispatchedSynchronously()
    {
        $this->expectException(LogicException::class);

        ResizeImage::dispatch('s3://bucket/cat.jpg', 800)->onConnection('sync');
    }

    public function testRemoteJobsCannotBeEncrypted()
    {
        $this->expectException(LogicException::class);

        EncryptedRemoteJob::dispatch();
    }

    public function testRemoteJobsCanBeFaked()
    {
        Queue::fake();

        ResizeImage::dispatch('s3://bucket/cat.jpg', 800);

        Queue::assertPushed(ResizeImage::class, fn ($job) => $job->width === 800);
    }

    protected function payload()
    {
        return json_decode(DB::table('jobs')->first()->payload, true);
    }

    /**
     * Do what a remote SDK does: acknowledge the job, then push the reply for Laravel to pick up.
     */
    protected function replyAsRemoteWorker(array $reply, array $outcome)
    {
        DB::table('jobs')->delete();

        Queue::pushRaw(json_encode([
            'uuid' => (string) Str::uuid(),
            'job' => $reply['job'],
            'data' => ['token' => $reply['token'], ...$outcome],
            'attempts' => 0,
        ]), $reply['queue']);

        Queue::pop($reply['queue'])->fire();
    }
}

#[Tries(3), Backoff([5, 30]), OnQueue('images')]
class ResizeImage implements ShouldRunRemotely
{
    use Queueable, InteractsWithRemoteWorker;

    public function __construct(public string $path, public int $width)
    {
        //
    }
}

#[RemoteName('videos.transcode')]
class TranscodeVideo implements ShouldRunRemotely
{
    use Queueable;

    public function __construct(public int $videoId)
    {
        //
    }

    public function toPayload(): array
    {
        return ['video' => ['id' => $this->videoId]];
    }
}

class EncryptedRemoteJob implements ShouldRunRemotely, ShouldBeEncrypted
{
    use Queueable;
}
