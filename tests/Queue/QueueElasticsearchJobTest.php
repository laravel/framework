<?php

use Mockery as m;
use Carbon\Carbon;

class QueueElasticsearchJobTest extends PHPUnit_Framework_TestCase
{
    private $elasticsearch;
    private $index;
    private $queueName;
    private $mockJob;
    private $mockData;
    private $mockPayload;
    private $mockId;
    private $mockContainer;
    private $mockJobData;
    private $queue;

    public function setUp()
    {
        $this->elasticsearch = $this->createMock('Elasticsearch\Client');
        $this->index = 'queue';
        $this->queueName = 'default';
        $this->mockJob = 'foo';
        $this->mockData = ['data'];
        $this->mockId = '1111';
        $this->mockPayload = json_encode(['id' => $this->mockId, 'job' => $this->mockJob, 'data' => $this->mockData]);

        $this->queue = $this->getMockBuilder('Illuminate\Queue\ElasticsearchQueue')->setMethods(['getQueue'])->setConstructorArgs([$this->elasticsearch, $this->index, $this->queueName, 60])->getMock();
        $this->mockContainer = m::mock('Illuminate\Container\Container');

        $this->mockJobData = [
            'id' => $this->mockId,
            'queue' => $this->queueName,
            'attempts' => 0,
            'reserved_at' => 0,
            'available_at' => Carbon::now()->getTimestamp(),
            'created_at' => Carbon::now()->getTimestamp(),
            'payload' => $this->mockPayload,
        ];
    }

    public function tearDown()
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('StdClass'));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);
        $job->fire();
    }

    public function testDeleteRemovesTheJobFromSqs()
    {
        $job = $this->getJob();

        $this->elasticsearch->expects($this->once())->method('delete')->with(['index' => $this->index, 'type' => $this->queueName, 'id' => $this->mockId]);
        $job->delete();
    }

    public function testReleaseProperlyReleasesTheJobOntoElasticsearch()
    {
        $job = $this->getJob();

        $params['index'] = $this->index;
        $params['type'] = $this->queueName;
        $params['id'] = $this->mockId;
        $params['body'] = [
            'id' => $this->mockId,
            'queue' => $this->queueName,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => Carbon::now()->getTimestamp(),
            'created_at' => Carbon::now()->getTimestamp(),
            'payload' => $this->mockPayload,
        ];

        $this->elasticsearch->expects($this->once())->method('delete')->with(['index' => $this->index, 'type' => $this->queueName, 'id' => $this->mockId]);
        $this->elasticsearch->expects($this->once())->method('index')->with($params);

        $job->release();

        $this->assertTrue($job->isReleased());
    }

    protected function getJob()
    {
        return new Illuminate\Queue\Jobs\ElasticsearchJob(
            $this->mockContainer,
            $this->queue,
            (object) $this->mockJobData,
            $this->queueName
        );
    }
}
