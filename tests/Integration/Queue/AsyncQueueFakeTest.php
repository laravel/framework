<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class AsyncQueueFakeTest extends TestCase
{
    /**
     * @var \Illuminate\Tests\Support\AsyncJobStub
     */
    private $job;

    /**
     * @var int
     */
    private $applicationRefreshes = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        $this->post = Post::create(['title' => 'xyz', 'slug' => 'xyz']);
    }

    public function testModelRehydrated()
    {
        Queue::fakeAsync(function () {
            Queue::resolveApplicationUsing(fn () => $this->queueApplication());

            Queue::push(
                new AsyncJobStub($this->post)
            );
        });

        Queue::assertPushed(AsyncJobStub::class);
    }

    public function testDeletedModelIsNotRehydrated()
    {
        $this->expectException(ModelNotFoundException::class);

        Queue::fakeAsync(function () {
            Queue::resolveApplicationUsing(fn () => $this->queueApplication());

            Queue::push(new AsyncJobStub($this->post));
            $this->post->delete();
        });
    }

    public function testSingletonsAreReset()
    {
        $instance = new AsyncSingletonStub;
        $instance->title = 'def';
        $this->app->singleton(AsyncSingletonStub::class, fn () => $instance);

        Queue::fakeAsync(function () {
            Queue::resolveApplicationUsing(fn () => $this->queueApplication());

            Queue::push(new ThirdAsyncJobStub($this->post));
        });

        $this->assertSame('abc', $this->post->fresh()->title);
    }

    public function testJobsAreAllDispatchedInANewProcess()
    {
        $job = new AsyncJobStub($this->post);

        Queue::fakeAsync(function () use ($job) {
            Queue::resolveApplicationUsing(fn () => $this->queueApplication());

            Queue::push($job);
            Queue::push($job);
        });

        Queue::assertPushed(AsyncJobStub::class, 2);
        $this->assertSame(2, $this->applicationRefreshes);
    }

    public function testOnlySpecifiedJobsAreRunAsynchronously()
    {
        $job = new AsyncJobStub($this->post);

        $queue = Queue::fakeAsync(function () use ($job) {
            Queue::resolveApplicationUsing(fn () => $this->queueApplication());

            Queue::push($job);
            Queue::push($job);
            Queue::push(new SecondAsyncJobStub);
        }, [AsyncJobStub::class]);

        Queue::assertPushed(AsyncJobStub::class, 2);
        $this->assertSame(2, $this->applicationRefreshes);
    }

    protected function queueApplication()
    {
        $this->applicationRefreshes++;

        return $this->createApplication();
    }
}

class AsyncJobStub implements ShouldQueue
{
    use SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function handle()
    {
        //
    }
}

class SecondAsyncJobStub implements ShouldQueue
{
    public function handle()
    {
        //
    }
}

class ThirdAsyncJobStub implements ShouldQueue
{
    use SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function handle()
    {
        $this->post->update(['title' => app(AsyncSingletonStub::class)->title]);
    }
}

class Post extends Model
{
    protected $guarded = [];

    public $table = 'posts';
}

class AsyncSingletonStub
{
    public $title = 'abc';
}
