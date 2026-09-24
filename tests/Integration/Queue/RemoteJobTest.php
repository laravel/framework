<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldRunRemotely;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\Attributes\RemoteName;
use Illuminate\Queue\Attributes\Service;
use Illuminate\Queue\InteractsWithRemoteWorker;
use Illuminate\Queue\RemoteJobFailed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        $app['config']->set('services.images', ['url' => 'http://images.test', 'token' => 'secret']);
    }

    protected function tearDown(): void
    {
        static::$result = null;

        parent::tearDown();
    }

    public function testCallSendsTheJobToTheServiceAndReturnsTheResponse()
    {
        Http::fake(['images.test/resize_image' => Http::response(['url' => 's3://bucket/cat-800.jpg'])]);

        $this->assertSame(['url' => 's3://bucket/cat-800.jpg'], ResizeImage::call('s3://bucket/cat.jpg', 800));

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://images.test/resize_image'
            && $request->hasHeader('Authorization', 'Bearer secret')
            && $request->data() === ['path' => 's3://bucket/cat.jpg', 'width' => 800]);
    }

    public function testCallThrowsWhenTheServiceFails()
    {
        Http::fake(['images.test/*' => Http::response(['message' => 'Unsupported format'], 422)]);

        $this->expectException(RemoteJobFailed::class);
        $this->expectExceptionMessage('Unsupported format');
        $this->expectExceptionCode(422);

        ResizeImage::call('s3://bucket/cat.psd', 800);
    }

    public function testNameAndDataMayBeCustomized()
    {
        Http::fake(['images.test/videos.transcode' => Http::response(['ok' => true])]);

        TranscodeVideo::call(42);

        Http::assertSent(fn (Request $request) => $request->data() === ['video' => ['id' => 42]]);
    }

    public function testServiceMustHaveAUrl()
    {
        config(['services.images.url' => null]);

        $this->expectExceptionObject(new LogicException('Service [images] does not have a URL configured.'));

        ResizeImage::call('s3://bucket/cat.jpg', 800);
    }

    public function testDispatchedJobCallsTheServiceFromTheQueueAndRunsThen()
    {
        Http::fake(['images.test/*' => Http::response(['url' => 's3://bucket/cat-800.jpg'])]);

        ResizeImage::dispatch('s3://bucket/cat.jpg', 800)
            ->then(fn (array $result) => RemoteJobTest::$result = $result['url']);

        Http::assertNothingSent();

        $uuid = json_decode(DB::table('jobs')->first()->payload)->uuid;

        Queue::pop()->fire();

        $this->assertSame('s3://bucket/cat-800.jpg', static::$result);

        Http::assertSent(fn (Request $request) => $request->hasHeader('Idempotency-Key', $uuid));
    }

    public function testClientErrorsFailTheJobWithoutRetrying()
    {
        Http::fake(['images.test/*' => Http::response(['message' => 'Unsupported format'], 422)]);

        ResizeImage::dispatch('s3://bucket/cat.psd', 800)
            ->catch(fn (RemoteJobFailed $e) => RemoteJobTest::$result = [$e->job, $e->getMessage(), $e->getCode()]);

        $job = Queue::pop();
        $job->fire();

        $this->assertTrue($job->hasFailed());
        $this->assertSame(['resize_image', 'Unsupported format', 422], static::$result);
    }

    public function testServerErrorsAreRetried()
    {
        Http::fake(['images.test/*' => Http::response('Bad Gateway', 502)]);

        ResizeImage::dispatch('s3://bucket/cat.jpg', 800)
            ->catch(fn () => RemoteJobTest::$result = 'caught');

        $this->expectException(RequestException::class);

        try {
            Queue::pop()->fire();
        } finally {
            $this->assertNull(static::$result);
        }
    }

    public function testRemoteJobsWorkInChains()
    {
        Http::fake(['images.test/*' => Http::response(['url' => 's3://bucket/cat-800.jpg'])]);

        Bus::chain([new ResizeImage('s3://bucket/cat.jpg', 800), new NotifyOwner])
            ->onConnection('sync')
            ->dispatch();

        Http::assertSentCount(1);
        $this->assertSame('notified', static::$result);
    }
}

#[Service('images')]
class ResizeImage implements ShouldRunRemotely
{
    use Queueable, InteractsWithRemoteWorker;

    public function __construct(public string $path, protected int $width)
    {
        //
    }
}

#[Service('images'), RemoteName('videos.transcode')]
class TranscodeVideo implements ShouldRunRemotely
{
    use Queueable, InteractsWithRemoteWorker;

    public function __construct(public int $videoId)
    {
        //
    }

    public function toPayload(): array
    {
        return ['video' => ['id' => $this->videoId]];
    }
}

class NotifyOwner implements ShouldQueue
{
    use Queueable;

    public function handle()
    {
        RemoteJobTest::$result = 'notified';
    }
}
