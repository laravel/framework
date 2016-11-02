<?php

use Mockery as m;
use Carbon\Carbon;

class QueueElasticsearchQueueTest extends PHPUnit_Framework_TestCase
{
    private $elasticsearch;
    private $index;
    private $queueName;
    private $mockJob;
    private $mockData;
    private $mockPayload;
    private $mockDelay;
    private $mockId;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        $this->elasticsearch = $this->createMock('Elasticsearch\Client');
        $this->index = 'queue';
        $this->queueName = 'default';
        $this->mockJob = 'foo';
        $this->mockData = ['data'];
        $this->mockId = '1111';
        $this->mockPayload = json_encode(['id' => $this->mockId, 'job' => $this->mockJob, 'data' => $this->mockData]);
        $this->mockDelay = 10;
    }

    public function testPopProperlyPopsJobOffOfElasticsearch()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\ElasticsearchQueue')->setMethods(['getQueue'])->setConstructorArgs([$this->elasticsearch, $this->index, $this->queueName, 60])->getMock();
        $queue->setContainer(m::mock('Illuminate\Container\Container'));

        $queue->expects($this->atLeastOnce())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueName));

        $params['index'] = $this->index;
        $params['type'] = $this->queueName;
        $params['body'] = [
            'query' => [
                'range' => [
                    'reserved_at' => [
                        'lte' => Carbon::now()->subSeconds(60)->getTimestamp(),
                    ]
                ]
            ]
        ];
        $params['size'] = 1;
        $params['sort'] = ['reserved_at:desc','available_at:desc'];

        $this->elasticsearch->expects($this->once())->method('search')->with($params);

        $queue->pop($this->queueName);
    }

    public function testDelayedPushProperlyPushesJobOntoElasticsearch()
    {
        $now = Carbon::now();

        $delay = 10;

        $queue = $this->getMockBuilder('Illuminate\Queue\ElasticsearchQueue')->setMethods(['getQueue','createPayload'])->setConstructorArgs([$this->elasticsearch, $this->index, $this->queueName, 60])->getMock();
        $queue->setContainer(m::mock('Illuminate\Container\Container'));

        $queue->expects($this->once())->method('createPayload')->with($this->mockJob, $this->mockData)->will($this->returnValue($this->mockPayload));
        $queue->expects($this->atLeastOnce())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueName));

        $params['index'] = $this->index;
        $params['type'] = $this->queueName;
        $params['id'] = $this->mockId;
        $params['body'] = [
            'id' => $this->mockId,
            'queue' => $this->queueName,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $now->getTimestamp()+$delay,
            'created_at' => $now->getTimestamp(),
            'payload' => $this->mockPayload,
        ];

        $this->elasticsearch->expects($this->once())->method('index')->with($params);

        $id = $queue->later($delay, $this->mockJob, $this->mockData, $this->queueName);

        $this->assertEquals($this->mockId, $id);
    }

    public function testPushProperlyPushesJobOntoElasticsearch()
    {
        $now = Carbon::now();

        $queue = $this->getMockBuilder('Illuminate\Queue\ElasticsearchQueue')->setMethods(['getQueue','createPayload'])->setConstructorArgs([$this->elasticsearch, $this->index, $this->queueName, 60])->getMock();
        $queue->setContainer(m::mock('Illuminate\Container\Container'));

        $queue->expects($this->once())->method('createPayload')->with($this->mockJob, $this->mockData)->will($this->returnValue($this->mockPayload));
        $queue->expects($this->atLeastOnce())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueName));

        $params['index'] = $this->index;
        $params['type'] = $this->queueName;
        $params['id'] = $this->mockId;
        $params['body'] = [
            'id' => $this->mockId,
            'queue' => $this->queueName,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $now->getTimestamp(),
            'created_at' => $now->getTimestamp(),
            'payload' => $this->mockPayload,
        ];

        $this->elasticsearch->expects($this->once())->method('index')->with($params);

        $id = $queue->push($this->mockJob, $this->mockData, $this->queueName);

        $this->assertEquals($this->mockId, $id);
    }

}
