<?php namespace Illuminate\Foundation\Bus;

use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Support\Collection;

trait DispatchesCommands {

	/**
	 * Dispatch a command to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	protected function dispatch($command)
	{
		return app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($command);
	}

	/**
	 * Marshal a command and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  array  $array
	 * @return mixed
	 */
	protected function dispatchFromArray($command, array $array)
	{
		return $this->dispatch($this->marshalFromArray($command, $array));
	}

	/**
	 * Marshal a command and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  array  $array
	 * @return mixed
	 */
	protected function dispatchFrom($command, ArrayAccess $source, $extras = [])
	{
		return $this->dispatch($this->marshal($command, $source, $extras));
	}

	/**
	 * Marshal a command from the given array.
	 *
	 * @param  string  $command
	 * @param  array  $array
	 * @return mixed
	 */
	public function marshalFromArray($command, array $array)
	{
		return $this->marshal($command, new Collection, $array);
	}

	/**
	 * Marshal a command from the given array accessible object.
	 *
	 * @param  string  $command
	 * @param  \ArrayAccess  $source
	 * @param  array  $extras
	 * @return mixed
	 */
	public function marshal($command, ArrayAccess $source, $extras = [])
	{
		$injected = [];

		$reflection = new ReflectionClass($command);

		if ($constructor = $reflection->getConstructor())
		{
			$injected = array_map(function($parameter) use ($command, $source, $extras)
			{
				return $this->getParameterValueForCommand($command, $source, $parameter, $extras);

			}, $constructor->getParameters());
		}

		return $reflection->newInstanceArgs($injected);
	}

	/**
	 * Get a parameter value for a marshalled command.
	 *
	 * @param  string  $command
	 * @param  \ArrayAccess  $source
	 * @param  \ReflectionParameter  $parameter
	 * @param  array  $extras
	 * @return mixed
	 */
	protected function getParameterValueForCommand($command, ArrayAccess $source,
                                                   ReflectionParameter $parameter, array $extras = array())
	{
		$value = $this->extractValueFromExtras($parameter, $extras)
								?: $this->extractValueFromSource($source, $parameter);

		if (is_null($value) && $parameter->isDefaultValueAvailable())
		{
			$value = $parameter->getDefaultValue();
		}
		elseif (is_null($value))
		{
			MarshalException::whileMapping($command, $parameter);
		}

		return $value;
	}

	/**
	 * Attempt to extract the given parameter out of the given array.
	 *
	 * @param  \ReflectionParameter  $parameter
	 * @param  array  $extras
	 * @return mixed
	 */
	protected function extractValueFromExtras(ReflectionParameter $parameter, array $extras)
	{
		return array_get($extras, $parameter->name, function() use ($parameter, $extras)
		{
			return array_get($extras, snake_case($parameter->name));
		});
	}

	/**
	 * Attempt to extract the given parameter out of the source.
	 *
	 * @param  \ArrayAccess  $source
	 * @param  \ReflectionParameter  $parameter
	 * @return mixed
	 */
	protected function extractValueFromSource(ArrayAccess $source, ReflectionParameter $parameter)
	{
		if (isset($source[$parameter->name]))
		{
			return $source[$parameter->name];
		}
		elseif (isset($source[snake_case($parameter->name)]))
		{
			return $source[snake_case($parameter->name)];
		}
	}

}
