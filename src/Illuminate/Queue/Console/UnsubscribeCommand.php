<?php namespace Illuminate\Queue\Console;

use RuntimeException;
use Illuminate\Queue\IronQueue;
use Illuminate\Queue\SqsQueue;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Log;

class UnsubscribeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:unsubscribe';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Unsubscribe a URL from an Iron.io or SQS push queue';

	/**
	 * The queue meta information
	 *
	 * @var object
	 */
	protected $meta;

	/**
	 * Execute the console command.
	 *
	 * @return void
	 *
	 * @throws \RuntimeException
	 */
	public function fire()
	{
		$queue = $this->laravel['queue']->connection();

		if ($queue instanceof IronQueue)
		{
			$queue->getIron()->updateQueue($this->argument('queue'), $this->getQueueOptions());
		} 
		else if ($queue instanceof SqsQueue)
		{
			Log::info('UnsubscribeCommand fire', array('list' => 'subscriptions'));

			$response = $queue->getSns()->listSubscriptions();

			Log::info('UnsubscribeCommand fire', array('listSubscriptions' => $response->toArray()));

			$endpoint = $this->argument('url');		
	
			$subscription = array_filter($response->toArray()['Subscriptions'], function($element) use ($endpoint) {
				
				return $element['Endpoint'] == $endpoint;
			});

			Log::info('UnsubscribeCommand fire', array('subscription' => $subscription));
	
			if(count($subscription)) {

				$response = $queue->getSns()->unsubscribe(array('SubscriptionArn' => $subscription[0]['SubscriptionArn']));

				Log::info('UnsubscribeCommand fire', array('response' => $response->toArray()));
			}
		} 
		else 
		{
			throw new RuntimeException("The default queue must be either IronMQ or SQS.");
		}

		$this->line('<info>Queue </info><comment>'.$this->argument('queue').'</comment><info> unsubscribed from:</info> <comment>'.$this->argument('url').'</comment>');
	}

	/**
	 * Get the queue options.
	 *
	 * @return array
	 */
	protected function getQueueOptions()
	{
		return array(
			'push_type' => $this->getPushType(), 'subscribers' => $this->getSubscriberList()
		);
	}

	/**
	 * Get the push type for the queue.
	 *
	 * @return string
	 */
	protected function getPushType()
	{
		if ($this->option('type')) return $this->option('type');

		try
		{
			return $this->getQueue()->push_type;
		}
		catch (\Exception $e)
		{
			return 'multicast';
		}
	}

	/**
	 * Get the current subscribers for the queue.
	 *
	 * @return array
	 */
	protected function getSubscriberList()
	{
		$subscribers = $this->getCurrentSubscribers();

		$subscribers[] = array('url' => $this->argument('url'));

		return $subscribers;
	}

	/**
	 * Get the current subscriber list.
	 *
	 * @return array
	 */
	protected function getCurrentSubscribers()
	{
		try
		{
			return $this->getQueue()->subscribers;
		}
		catch (\Exception $e)
		{
			return array();
		}
	}

	/**
	 * Get the queue information
	 *
	 * @return object
	 */
	protected function getQueue()
	{
		if (isset($this->meta)) return $this->meta;

		$queue = $this->laravel['queue']->connection();

		if ($queue instanceof IronQueue)
		{
			Log::info('UnsubscribeCommand getQueue', array('queue metadata' => $this->laravel['queue']->getIron()->getQueue($this->argument('queue')))); 

			return $this->meta = $this->laravel['queue']->getIron()->getQueue($this->argument('queue'));
		} 
		else if ($queue instanceof SqsQueue)
		{
			Log::info('UnsubscribeCommand getQueue', array('queue metadata' => $this->laravel['queue']->getSqs()->getQueueAttributes(array('QueueUrl' => $this->argument('queue'))))); 

			return $this->meta = $this->laravel['queue']->getSqs()->getQueueAttributes(array('QueueUrl' => $this->argument('queue')));
		} 
		else 
		{
			throw new RuntimeException("The default queue must be either IronMQ or SQS.");
		}
	
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('queue', InputArgument::REQUIRED, 'The name of Iron.io queue or SNS topic.'),

			array('url', InputArgument::REQUIRED, 'The URL to be unsubscribed.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('type', null, InputOption::VALUE_OPTIONAL, 'The push type for the queue.'),
		);
	}

}
