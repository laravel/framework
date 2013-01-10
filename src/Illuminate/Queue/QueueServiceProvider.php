<?php namespace Illuminate\Queue;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Console\ListenCommand;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Connectors\BeanstalkdConnector;

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

		$this->registerListener();
	}

	/**
	 * Register the queue manager.
	 *
	 * @return void
	 */
	protected function registerManager()
	{
		$me = $this;

		$this->app['queue'] = $this->app->share(function($app) use ($me)
		{
			// Once we have an instance of the queue manager, we will register the various
			// resolvers for the queue connectors. These connectors are responsible for
			// creating the classes that accept queue configs and instantiate queues.
			$manager = new QueueManager($app);

			$me->registerConnectors($manager);

			return $manager;
		});
	}

	/**
	 * Register the queue listener.
	 *
	 * @return void
	 */
	protected function registerListener()
	{
		$this->registerListenerCommand();

		$this->app['queue.listener'] = $this->app->share(function($app)
		{
			return new Listener($app['queue']);
		});
	}

	/**
	 * Register the queue listener console command.
	 *
	 * @return void
	 */
	protected function registerListenerCommand()
	{
		$app = $this->app;

		$app['command.queue.listen'] = $app->share(function($app)
		{
			return new ListenCommand($app['queue.listener']);
		});
	}

	/**
	 * Register the connectors on the queue manager.
	 *
	 * @param  Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	public function registerConnectors($manager)
	{
		foreach (array('Sync', 'Beanstalkd') as $connector)
		{
			$this->{"register{$connector}Connector"}($manager);
		}
	}

	/**
	 * Register the Sync queue connector.
	 *
	 * @param  Illuminate\Queue\QueueManager  $manager
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
	 * @param  Illuminate\Queue\QueueManager  $manager
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
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('queue', 'queue.listener', 'command.queue.listen');
	}

}