<?php

use Mockery as m;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Http\Response;

class QueueRabbitJobTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function setUp() {

		$this->job = 'foo';
		$this->data = array('data');
		$this->queue = 'default';

		$this->payload = json_encode(array('job' => $this->job, 'data' => $this->data, 'attempts' => 1, 'queue' => $this->queue));
		$this->recreatedPayload = json_encode(array('job' => $this->job, 'data' => $this->data, 'attempts' => 2, 'queue' => $this->queue));

		$this->message = new AMQPMessage($this->payload, array('delivery_mode' => 2));
		$this->message->delivery_info = array(
			"channel" => 'foo',
			"consumer_tag" => 'bar',
			"delivery_tag" => 1,
			"redelivered" => false,
			"exchange" => "",
			"routing_key" => $this->queue
		);
	}

	public function testFireProperlyCallsTheJobHandler()
	{
		$job = $this->getJob();
		$job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('fire')->once()->with($job, array('data'));
		$job->fire();
	}

	public function testDeleteSendsAckToRemoveJob()
	{
		$job = $this->getJob();
		$job->getRabbit()->shouldReceive('getChannel')->twice()->andReturn(m::mock('PhpAmqpLib\Channel\AMQPChannel'));
		$job->getRabbit()->getChannel()->shouldReceive('basic_ack')->once()->with($this->message->delivery_info['delivery_tag'])->andReturn(null);
		$job->delete();
        }

	public function testReleaseProperlyReleasesJobOntoRabbit()
	{
		$job = $this->getJob();
		$job->getRabbit()->shouldReceive('getChannel')->twice()->andReturn(m::mock('PhpAmqpLib\Channel\AMQPChannel'));
		$job->getRabbit()->getChannel()->shouldReceive('basic_ack')->once()->with($this->message->delivery_info['delivery_tag'])->andReturn(null);
		$job->getRabbit()->shouldReceive('recreate')->once()->with(json_encode(array('job' => 'foo', 'data' => array('data'), 'attempts' => 2, 'queue' => 'default')), 'default', 5);
		$job->release(5);
	}

	public function testAttemptsReturnsCurrentCountOfAttempts()
	{
		$job = $this->getJob();
		$one = $job->attempts();
		$this->assertEquals($one, 1);
	}

	protected function getJob()
	{
		return new Illuminate\Queue\Jobs\RabbitJob(
			m::mock('Illuminate\Container\Container'),
			m::mock('Illuminate\Queue\RabbitQueue'),
			$this->message
		);
	}

}
