<?php

use Mockery as m;

use Guzzle\Service\Resource\Model;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class QueueSubscribeTest extends PHPUnit_Framework_TestCase {

	public function setUp() {

		parent::setUp();	

		$this->command = new Illuminate\Queue\Console\SubscribeCommand;

		$this->sns = m::mock('Aws\Sns\SnsClient');
		$this->sqs = m::mock('Aws\Sqs\SqsClient');

		$this->region = 'someregion';
		$this->account = '1234567891011';
		$this->queueName = 'notifications';
		$this->topicName = 'notifications';
		$this->errorQueueName = 'emails';
	
		$this->endpointUrl = 'http://www.somedomain.com/receive/notifications';	
		$this->mockedTopicArn = 'arn:aws:sns:'.$this->region.':'.$this->account.':'.$this->topicName;
		$this->mockedSubscriptionArn = $this->mockedTopicArn . ':foo';

		$this->mockedCreateTopicResponseModel = new Model(array('TopicArn' => $this->mockedTopicArn));
		$this->mockedSubscribeResponseModel = new Model(array('SubscriptionArn' => $this->mockedSubscriptionArn));

		$this->app = m::mock('AppMock');
		$this->app->shouldReceive('instance')->once()->andReturn($this->app);

		Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
		Illuminate\Support\Facades\Config::swap($this->config = m::mock('ConfigMock'));
	}

	public function tearDown()
	{
		m::close();
	}

	public function testSubscribeCommandCreatesTopicAndSubscribesToSqs()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('connection'), array($this->sqs, $this->sns, m::mock('Illuminate\Http\Request'), $this->account, $this->queueName));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$app = $this->getQueueConfigForTest($queue, 'sqs');
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$this->sns->shouldReceive('createTopic')->once()->with(array('Name' => $this->queueName))->andReturn($response = $this->mockedCreateTopicResponseModel);
		$this->sns->shouldReceive('subscribe')->once()->with(array('TopicArn' => $response->get('TopicArn'), 'Protocol' => ((stripos($this->endpointUrl, 'https') !== false) ? 'https' : 'http'), 'Endpoint' => $this->endpointUrl))->andReturn($response = $this->mockedSubscribeResponseModel);
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl, '--advanced' => '{}')), new NullOutput);
		$this->assertEquals($this->mockedSubscriptionArn, $response->get('SubscriptionArn'));
	}

	public function testSubscribeCommandCreatesQueueAndSubscribesToIron()
	{
		$queue = $this->getMock('Illuminate\Queue\IronQueue', array('connection'), array($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default', true));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$app = $this->getQueueConfigForTest($queue, 'iron');
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$iron->shouldReceive('getQueue')->once()->with($this->queueName)->andReturn($queue);	
		//$queue->expects($this->any())->method('getQueueOptions')->with($this->queueName, $this->endpointUrl, array('retries' => '3', 'errqueue' => $this->errorQueueName), array('type' => 'multicast', 'retries_delay' => 120))->will($this->returnValue(array('retries' => '3', 'errqueue' => $this->errorQueueName, 'push_type' => 'multicast', 'retries_delay' => 120)));
		$iron->shouldReceive('updateQueue')->once()->with($this->queueName, array('subscribers' => array('0' => array('url' => $this->endpointUrl)), 'retries' => 3, 'error_queue' => $this->errorQueueName))->andReturn($response = (object) array('msg' => 'foo'));
		
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl, '--retries' => 3, '--errqueue' => $this->errorQueueName, '--advanced' => '{}')), new NullOutput);
		$this->assertEquals('foo', $response->msg);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testSubscribeCommandThrowsRuntimeExceptionForBeanstalkd()
	{
		$queue = $this->getMock('Illuminate\Queue\BeanstalkdQueue', array('connection'), array(m::mock('Pheanstalk_Pheanstalk'), 'default'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$this->config->shouldReceive('get')->once()->with('queue.default')->andReturn('beanstalkd');
		$app = $this->getQueueConfigForTest($queue, 'beanstalkd');
		$queue->app = $app;
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl, '--advanced' => '{}')), new NullOutput);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testSubscribeCommandThrowsRuntimeExceptionForRedis()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('connection'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$this->config->shouldReceive('get')->once()->with('queue.default')->andReturn('redis');
		$app = $this->getQueueConfigForTest($queue, 'redis');
		$queue->app = $app;
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl, '--advanced' => '{}')), new NullOutput);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testSubscribeCommandThrowsRuntimeExceptionForSync()
	{
		$queue = $this->getMock('Illuminate\Queue\SyncQueue', array('connection'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$this->config->shouldReceive('get')->once()->with('queue.default')->andReturn('sync');
		$app = $this->getQueueConfigForTest($queue, 'sync');
		$queue->app = $app;
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl, '--advanced' => '{}')), new NullOutput);
	}

	public function getQueueConfigForTest($queue, $driver)
	{
		return array('config' => array('queue.default' => $driver, 'queue.connections.'.$driver => array('driver' => $driver)), 'queue' => $queue);
	}
}
