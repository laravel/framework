<?php

use Mockery as m;

use Guzzle\Service\Resource\Model;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class QueueUnsubscribeTest extends PHPUnit_Framework_TestCase {

	public function setUp() {

		parent::setUp();	

		$this->command = new Illuminate\Queue\Console\UnsubscribeCommand;

		$this->sns = m::mock('Aws\Sns\SnsClient');
		$this->sqs = m::mock('Aws\Sqs\SqsClient');

		$this->region = 'someregion';
		$this->account = '1234567891011';
		$this->queueName = 'notifications';
		$this->topicName = 'notifications';
		
		$this->endpointUrl = 'http://www.somedomain.com/receive/notifications';	
		$this->mockedTopicArn = 'arn:aws:sns:'.$this->region.':'.$this->account.':'.$this->topicName;
		$this->mockedSubscriptionArn = $this->mockedTopicArn . ':foo';
		$this->mockedRequestId = 'foo';

		$this->mockedListSubscriptionsResponseModel = new Model(array('Subscriptions' => array( 0 => array('Protocol' => 'http', 'Owner' => $this->account, 'TopicArn' => $this->mockedTopicArn, 'SubscriptionArn' => $this->mockedSubscriptionArn, 'Endpoint' => $this->endpointUrl)), 'ResponseMetadata' => array('RequestId' => $this->mockedRequestId)));
		$this->mockedUnsubscribeResponseModel = new Model(array());

		$this->app = m::mock('AppMock');
		$this->app->shouldReceive('instance')->once()->andReturn($this->app);

		Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
		Illuminate\Support\Facades\Config::swap($this->config = m::mock('ConfigMock'));
	}

	public function tearDown()
	{
		m::close();
	}

	public function testUnsubscribeCommandUnsubscribesFromSnsTopic()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('connection'), array($this->sqs, $this->sns, m::mock('Illuminate\Http\Request'), $this->account, $this->queueName));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$app = $this->getQueueConfigForTest($queue, 'sqs');
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));	
		$this->sns->shouldReceive('listSubscriptions')->once()->andReturn($response = $this->mockedListSubscriptionsResponseModel);
		$this->sns->shouldReceive('unsubscribe')->once()->with(array('SubscriptionArn' => $this->mockedSubscriptionArn))->andReturn($this->mockedUnsubscribeResponseModel);
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl)), new NullOutput);
	}

	public function testUnsubscribeCommandUnsubscribesFromIronQueue()
	{
		$queue = $this->getMock('Illuminate\Queue\IronQueue', array('connection'), array($iron = m::mock('IronMQ'), $crypt = m::mock('Illuminate\Encryption\Encrypter'), m::mock('Illuminate\Http\Request'), 'default', true));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$app = $this->getQueueConfigForTest($queue, 'iron');
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$iron->shouldReceive('removeSubscriber')->once()->with($this->queueName, array('url' => $this->endpointUrl))->andReturn($response = (object) array('msg' => 'Updated'));
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl)), new NullOutput);
		$this->assertEquals('Updated', $response->msg);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testUnsubscribeCommandThrowsRuntimeExceptionForBeanstalkd()
	{
		$queue = $this->getMock('Illuminate\Queue\BeanstalkdQueue', array('connection'), array(m::mock('Pheanstalk_Pheanstalk'), 'default'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$this->config->shouldReceive('get')->once()->with('queue.default')->andReturn('beanstalkd');
		$app = $this->getQueueConfigForTest($queue, 'beanstalkd');
		$queue->app = $app;
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl)), new NullOutput);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testUnSubscribeCommandThrowsRuntimeExceptionForRedis()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('connection'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$this->config->shouldReceive('get')->once()->with('queue.default')->andReturn('redis');
		$app = $this->getQueueConfigForTest($queue, 'redis');
		$queue->app = $app;
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl)), new NullOutput);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testUnsubscribeCommandThrowsRuntimeExceptionForSync()
	{
		$queue = $this->getMock('Illuminate\Queue\SyncQueue', array('connection'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$this->config->shouldReceive('get')->once()->with('queue.default')->andReturn('sync');
		$app = $this->getQueueConfigForTest($queue, 'sync');
		$queue->app = $app;
		$queue->expects($this->any())->method('connection')->will($this->returnValue($queue));
		$this->command->setLaravel($app);
		$this->command->run(new ArrayInput(array('queue' => $this->queueName, 'url' => $this->endpointUrl)), new NullOutput);
	}

	public function getQueueConfigForTest($queue, $driver)
	{
		return array('config' => array('queue.default' => $driver, 'queue.connections.'.$driver => array('driver' => $driver)), 'queue' => $queue);
	}

}
