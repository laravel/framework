<?php namespace Illuminate\Events;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Container\Container;
use ReflectionMethod;

class CallQueuedHandler {

	/**
	 * The container instance.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Handle the queued job.
	 *
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @param  array  $data
	 * @return void
	 */
	public function call(Job $job, array $data)
	{
		$handler = $this->setJobInstanceIfNecessary(
			$job, $this->container->make($data['class'])
		);

		$jobData = unserialize($data['data']);
		$this->parameterizeJobDataDependencies($handler, $data['method'], $jobData);
		$this->container->call(
			[$handler, $data['method']], $jobData
		);

		if ( ! $job->isDeletedOrReleased())
		{
			$job->delete();
		}
	}

	/**
	 * Convert the job data into named parameters that the IoC container can use to resolve dependencies
	 *
	 * @param  object $handler
	 * @param  string $method
	 * @param  array  $data
	 * @return void
	 */
	protected function parameterizeJobDataDependencies($handler, $method, &$data)
	{
		foreach ((new ReflectionMethod($handler, $method))->getParameters() as $callbackKey => $callbackParameter) {
			$callbackParameterClass = ($callbackParameter->getClass()) ? $callbackParameter->getClass()->name : null;
			if (empty($callbackParameterClass))
				continue;
			$callbackParameterName = $callbackParameter->name;
			foreach ($data as $dataKey => &$possibleParameter) {
				if (!is_object($possibleParameter) || !is_numeric($dataKey))
					continue;
				if (get_class($possibleParameter) == $callbackParameterClass) {
					$data[$callbackParameterName] = $possibleParameter;
					unset($data[$dataKey]);
				}
			}
			unset($possibleParameter);
		}
	}

	/**
	 * Set the job instance of the given class if necessary.
	 *
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @param  mixed  $instance
	 * @return mixed
	 */
	protected function setJobInstanceIfNecessary(Job $job, $instance)
	{
		if (in_array('Illuminate\Queue\InteractsWithQueue', class_uses_recursive(get_class($instance))))
		{
			$instance->setJob($job);
		}

		return $instance;
	}

	/**
	 * Call the failed method on the job instance.
	 *
	 * @return void
	 */
	public function failed(array $data)
	{
		$handler = $this->container->make($data['class']);

		if (method_exists($handler, 'failed'))
		{
			call_user_func_array([$handler, 'failed'], unserialize($data));
		}
	}

}
