<?php namespace Illuminate\Queue;

use IlluminateQueueClosure;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Queue\Console\ListenCommand;
use Illuminate\Queue\Console\RestartCommand;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Console\SubscribeCommand;
use Illuminate\Queue\Connectors\NullConnector;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Connectors\IronConnector;
use Illuminate\Queue\Connectors\RedisConnector;
use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Queue\Connectors\BeanstalkdConnector;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;

class QueueServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerManager();

		$this->registerWorker();

		$this->registerListener();

		$this->registerSubscriber();

		$this->registerFailedJobServices();

		$this->registerQueueClosure();
	}

	/**
	 * Register the queue manager.
	 *
	 * @return void
	 */
	protected function registerManager()
	{
		$this->app->singleton('queue', function()
		{
			// Once we have an instance of the queue manager, we will register the various
			// resolvers for the queue connectors. These connectors are responsible for
			// creating the classes that accept queue configs and instantiate queues.
			$manager = new QueueManager($this->app);

			$this->registerConnectors($manager);

			return $manager;
		});

		$this->app->singleton('queue.connection', function()
		{
			return $this->app['queue']->connection();
		});
	}

	/**
	 * Register the queue worker.
	 *
	 * @return void
	 */
	protected function registerWorker()
	{
		$this->registerWorkCommand();

		$this->registerRestartCommand();

		$this->app->singleton('queue.worker', function()
		{
			return new Worker($this->app['queue'], $this->app['queue.failer'], $this->app['events']);
		});
	}

	/**
	 * Register the queue worker console command.
	 *
	 * @return void
	 */
	protected function registerWorkCommand()
	{
		$this->app->singleton('command.queue.work', function()
		{
			return new WorkCommand($this->app['queue.worker']);
		});

		$this->commands('command.queue.work');
	}

	/**
	 * Register the queue listener.
	 *
	 * @return void
	 */
	protected function registerListener()
	{
		$this->registerListenCommand();

		$this->app->singleton('queue.listener', function()
		{
			return new Listener($this->app['path.base']);
		});
	}

	/**
	 * Register the queue listener console command.
	 *
	 * @return void
	 */
	protected function registerListenCommand()
	{
		$this->app->singleton('command.queue.listen', function()
		{
			return new ListenCommand($this->app['queue.listener']);
		});

		$this->commands('command.queue.listen');
	}

	/**
	 * Register the queue restart console command.
	 *
	 * @return void
	 */
	public function registerRestartCommand()
	{
		$this->app->singleton('command.queue.restart', function()
		{
			return new RestartCommand;
		});

		$this->commands('command.queue.restart');
	}

	/**
	 * Register the push queue subscribe command.
	 *
	 * @return void
	 */
	protected function registerSubscriber()
	{
		$this->app->singleton('command.queue.subscribe', function()
		{
			return new SubscribeCommand;
		});

		$this->commands('command.queue.subscribe');
	}

	/**
	 * Register the connectors on the queue manager.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	public function registerConnectors($manager)
	{
		foreach (array('Null', 'Sync', 'Database', 'Beanstalkd', 'Redis', 'Sqs', 'Iron') as $connector)
		{
			$this->{"register{$connector}Connector"}($manager);
		}
	}

	/**
	 * Register the Null queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerNullConnector($manager)
	{
		$manager->addConnector('null', function()
		{
			return new NullConnector;
		});
	}

	/**
	 * Register the Sync queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerSyncConnector($manager)
	{
		$manager->addConnector('sync', function()
		{
			return new SyncConnector;
		});
	}

	/**
	 * Register the Beanstalkd queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerBeanstalkdConnector($manager)
	{
		$manager->addConnector('beanstalkd', function()
		{
			return new BeanstalkdConnector;
		});
	}

	/**
	 * Register the database queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerDatabaseConnector($manager)
	{
		$manager->addConnector('database', function()
		{
			return new DatabaseConnector($this->app['db']);
		});
	}

	/**
	 * Register the Redis queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerRedisConnector($manager)
	{
		$manager->addConnector('redis', function()
		{
			return new RedisConnector($this->app['redis']);
		});
	}

	/**
	 * Register the Amazon SQS queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerSqsConnector($manager)
	{
		$manager->addConnector('sqs', function()
		{
			return new SqsConnector;
		});
	}

	/**
	 * Register the IronMQ queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerIronConnector($manager)
	{
		$manager->addConnector('iron', function()
		{
			return new IronConnector($this->app['encrypter'], $this->app['request']);
		});

		$this->registerIronRequestBinder();
	}

	/**
	 * Register the request rebinding event for the Iron queue.
	 *
	 * @return void
	 */
	protected function registerIronRequestBinder()
	{
		$this->app->rebinding('request', function($request)
		{
			if ($this->app['queue']->connected('iron'))
			{
				$this->app['queue']->connection('iron')->setRequest($request);
			}
		});
	}

	/**
	 * Register the failed job services.
	 *
	 * @return void
	 */
	protected function registerFailedJobServices()
	{
		$this->app->singleton('queue.failer', function()
		{
			$config = $this->app['config']['queue.failed'];

			return new DatabaseFailedJobProvider($this->app['db'], $config['database'], $config['table']);
		});
	}

	/**
	 * Register the Illuminate queued closure job.
	 *
	 * @return void
	 */
	protected function registerQueueClosure()
	{
		$this->app->singleton('IlluminateQueueClosure', function()
		{
			return new IlluminateQueueClosure($this->app['encrypter']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'queue', 'queue.worker', 'queue.listener', 'queue.failer',
			'command.queue.work', 'command.queue.listen', 'command.queue.restart',
			'command.queue.subscribe', 'queue.connection',
		);
	}

}
