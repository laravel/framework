<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Testing\Fakes\AsyncQueueFake;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class SupportTestingAsyncQueueFakeTest extends TestCase
{
    /**
     * @var \Illuminate\Support\Testing\Fakes\AsyncQueueFake
     */
    private $fake;

    /**
     * @var \Illuminate\Tests\Support\JobStub
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

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testModelRehydrated()
    {
        $job = new JobStub($this->post);
        $queue = $this->mockQueueManager();

        $queue->push($job);
        $queue->dispatch();

        $queue->assertPushed(JobStub::class);
    }

    public function testDeletedModelIsNotRehydrated()
    {
        $this->expectException(ModelNotFoundException::class);

        $job = new JobStub($this->post);
        $queue = $this->mockQueueManager();

        $queue->push($job);
        $this->post->delete();
        $queue->dispatch();
    }

    public function testJobsAreAllDispatchedInANewProcess()
    {
        $job = new JobStub($this->post);
        $queue = $this->mockQueueManager();

        $queue->push($job);
        $queue->push($job);
        $queue->dispatch();

        $queue->assertPushed(JobStub::class, 2);
        $this->assertSame(2, $this->applicationRefreshes);
    }

    public function testOnlySpecifiedJobsAreRunAsynchronously()
    {
        $job = new JobStub($this->post);
        $queue = $this->mockQueueManager([JobStub::class]);

        $queue->push($job);
        $queue->push($job);
        $queue->push(new SecondJobStub);
        $queue->dispatch();

        $queue->assertPushed(JobStub::class, 2);
        $this->assertSame(2, $this->applicationRefreshes);
    }

    protected function refreshQueueApplication()
    {
        $db = $this->app->make('db');
        $queue = $this->app->make('queue');

        $app = parent::refreshApplication();

        Queue::swap($queue);
        DB::swap($db);
        Model::setConnectionResolver($db);

        $this->applicationRefreshes++;

        return $app;
    }

    protected function mockQueueManager($jobsToRunAsynchronously = [], $app = null, $queue = null)
    {
        $queueManager = m::mock(
            AsyncQueueFake::class, [
                $app ?: $this->app,
                $jobsToRunAsynchronously,
                $queue ?: $this->app->make('queue'),
            ])
        ->makePartial();
        $queueManager->shouldAllowMockingProtectedMethods();
        $queueManager->shouldReceive('refreshApplication')
            ->andReturnUsing(fn () => $this->refreshQueueApplication());

        return $queueManager;
    }
}

class JobStub implements ShouldQueue
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

class SecondJobStub implements ShouldQueue
{
    public function handle()
    {
        //
    }
}

class Post extends Model
{
    protected $guarded = [];

    public $table = 'posts';
}
