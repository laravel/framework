<?php namespace Illuminate\Foundation;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Filesystem\ClassFinder;

class EventCache {

	/**
	 * The class finder instance.
	 *
	 * @var \Illuminate\Filesystem\ClassFinder  $finder
	 */
	protected $finder;

	/**
	 * The event registration stub.
	 *
	 * @var string
	 */
	protected $stub = '$events->listen(\'{{event}}\', \'{{handler}}\');';

	/**
	 * Create a new event cache instance.
	 *
	 * @param  \Illuminate\Filesystem\ClassFinder  $finder
	 * @return void
	 */
	public function __construct(ClassFinder $finder)
	{
		$this->finder = $finder;
	}

	/**
	 * Get the contents that should be written to a cache file.
	 *
	 * @param  array  $paths
	 * @return string
	 */
	public function get(array $paths)
	{
		$cache = '<?php $events = app(\'events\');'.PHP_EOL.PHP_EOL;

		foreach ($paths as $path)
		{
			$cache .= $this->getCacheForPath($path);
		}

		return $cache;
	}

	/**
	 * Get the cache contents for a given path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	protected function getCacheForPath($path)
	{
		$cache = '';

		foreach ($this->finder->findClasses($path) as $class)
		{
			$cache .= $this->getCacheForClass(new ReflectionClass($class));
		}

		return $cache;
	}

	/**
	 * Get the cache for the given class reflection.
	 *
	 * @param  \ReflectionClass  $reflection
	 * @return string|null
	 */
	protected function getCacheForClass(ReflectionClass $reflection)
	{
		if ($reflection->isAbstract() && ! $reflection->isInterface())
		{
			continue;
		}

		foreach ($reflection->getMethods() as $method)
		{
			return $this->getCacheForMethod($method);
		}
	}

	/**
	 * Get the cache for the given method reflection.
	 *
	 * @param  \ReflectionMethod  $method
	 * @return string|null
	 */
	protected function getCacheForMethod(ReflectionMethod $method)
	{
		preg_match_all('/@hears(.*)/', $method->getDocComment(), $matches);

		$events = [];

		if (isset($matches[1]) && count($matches[1]) > 0)
		{
			$events = array_map('trim', $matches[1]);
		}

		foreach ($events as $event)
		{
			return $this->formatEventStub($method, $event);
		}
	}

	/**
	 * Format the event listener stub for the given method and event.
	 *
	 * @param  \ReflectionMethod  $method
	 * @param  string  $event
	 * @return string
	 */
	protected function formatEventStub(ReflectionMethod $method, $event)
	{
		$event = str_replace('{{event}}', $event, $this->stub);

		return str_replace('{{handler}}', $method->class.'@'.$method->name, $event).PHP_EOL;
	}

}