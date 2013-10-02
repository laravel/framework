<?php namespace Illuminate\Container;

use Closure;
use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;

class BindingResolutionException extends \Exception {}

class Container implements ArrayAccess {

	/**
	 * The container's bindings.
	 *
	 * @var array
	 */
	protected $bindings = array();

	/**
	 * The container's shared instances.
	 *
	 * @var array
	 */
	protected $instances = array();

	/**
	 * The registered type aliases.
	 *
	 * @var array
	 */
	protected $aliases = array();

	/**
	 * All of the registered resolving callbacks.
	 *
	 * @var array
	 */
	protected $resolvingCallbacks = array();

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	public function bound($abstract)
	{
		return isset($this[$abstract]) or isset($this->instances[$abstract]);
	}

	/**
	 * Register a binding with the container.
	 *
	 * @param  string               $abstract
	 * @param  Closure|string|null  $concrete
	 * @param  bool                 $shared
	 * @return void
	 */
	public function bind($abstract, $concrete = null, $shared = false)
	{
		// If the given type is actually an array, we'll assume an alias is being
		// defined and will grab the real abstract class name and register the
		// alias with the container so it can be used as a short-cut for it.
		if (is_array($abstract))
		{
			list($abstract, $alias) = $this->extractAlias($abstract);

			$this->alias($abstract, $alias);
		}

		// If no concrete type was given, we will simply set the concrete type to
		// the abstract. This allows concrete types to be registered as shared
		// without being made state their classes in both of the parameters.
		unset($this->instances[$abstract]);

		if (is_null($concrete))
		{
			$concrete = $abstract;
		}

		// If the factory is not a Closure, it means it is just a class name that
		// is bound into the container to an abstract type and we'll just wrap
		// it up in a Closure to make things more convenient when extending.
		if ( ! $concrete instanceof Closure)
		{
			$concrete = function($c) use ($abstract, $concrete)
			{
				$method = ($abstract == $concrete) ? 'build' : 'make';

				return $c->$method($concrete);
			};
		}

		$this->bindings[$abstract] = compact('concrete', 'shared');
	}

	/**
	 * Register a binding if it hasn't already been registered.
	 *
	 * @param  string               $abstract
	 * @param  Closure|string|null  $concrete
	 * @param  bool                 $shared
	 * @return bool
	 */
	public function bindIf($abstract, $concrete = null, $shared = false)
	{
		if ( ! $this->bound($abstract))
		{
			$this->bind($abstract, $concrete, $shared);
		}
	}

	/**
	 * Register a shared binding in the container.
	 *
	 * @param  string               $abstract
	 * @param  Closure|string|null  $concrete
	 * @return void
	 */
	public function singleton($abstract, $concrete = null)
	{
		return $this->bind($abstract, $concrete, true);
	}

	/**
	 * Wrap a Closure such that it is shared.
	 *
	 * @param  Closure  $closure
	 * @return Closure
	 */
	public function share(Closure $closure)
	{
		return function($container) use ($closure)
		{
			// We'll simply declare a static variable within the Closures and if
			// it has not been set we'll execute the given Closure to resolve
			// the value and return it back to the consumers of the method.
			static $object;

			if (is_null($object))
			{
				$object = $closure($container);
			}

			return $object;
		};
	}

	/**
	 * "Extend" an abstract type in the container.
	 *
	 * @param  string   $abstract
	 * @param  Closure  $closure
	 * @return void
	 */
	public function extend($abstract, Closure $closure)
	{
		if ( ! isset($this->bindings[$abstract]))
		{
			throw new \InvalidArgumentException("Type {$abstract} is not bound.");
		}

		// To "extend" a binding, we will grab the old "resolver" Closure and pass it
		// into a new one. The old resolver will be called first and the result is
		// handed off to the "new" resolver, along with this container instance.
		$resolver = $this->bindings[$abstract]['concrete'];

		$this->bind($abstract, function($container) use ($resolver, $closure)
		{
			return $closure($resolver($container), $container);

		}, $this->isShared($abstract));
	}

	/**
	 * Register an existing instance as shared in the container.
	 *
	 * @param  string  $abstract
	 * @param  mixed   $instance
	 * @return void
	 */
	public function instance($abstract, $instance)
	{
		if (is_array($abstract))
		{
			list($abstract, $alias) = $this->extractAlias($abstract);

			$this->alias($abstract, $alias);
		}

		$this->instances[$abstract] = $instance;
	}

	/**
	 * Alias a type to a shorter name.
	 *
	 * @param  string  $abstract
	 * @param  string  $alias
	 * @return void
	 */
	public function alias($abstract, $alias)
	{
		$this->aliases[$alias] = $abstract;
	}

	/**
	 * Extract the type and alias from a given definition.
	 *
	 * @param  array  $definition
	 * @return array
	 */
	protected function extractAlias(array $definition)
	{
		return array(key($definition), current($definition));
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @param  string  $abstract
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function make($abstract, $parameters = array())
	{
		$abstract = $this->getAlias($abstract);

		// If an instance of the type is currently being managed as a singleton we'll
		// just return an existing instance instead of instantiating new instances
		// so the developer can keep using the same objects instance every time.
		if (isset($this->instances[$abstract]))
		{
			return $this->instances[$abstract];
		}

		$concrete = $this->getConcrete($abstract);

		// We're ready to instantiate an instance of the concrete type registered for
		// the binding. This will instantiate the types, as well as resolve any of
		// its "nested" dependencies recursively until all have gotten resolved.
		if ($this->isBuildable($concrete, $abstract))
		{
			$object = $this->build($concrete, $parameters);
		}
		else
		{
			$object = $this->make($concrete, $parameters);
		}

		// If the requested type is registered as a singleton we'll want to cache off
		// the instances in "memory" so we can return it later without creating an
		// entirely new instance of an object on each subsequent request for it.
		if ($this->isShared($abstract))
		{
			$this->instances[$abstract] = $object;
		}

		$this->fireResolvingCallbacks($object);

		return $object;
	}

	/**
	 * Get the concrete type for a given abstract.
	 *
	 * @param  string  $abstract
	 * @return mixed   $concrete
	 */
	protected function getConcrete($abstract)
	{
		// If we don't have a registered resolver or concrete for the type, we'll just
		// assume each type is a concrete name and will attempt to resolve it as is
		// since the container should be able to resolve concretes automatically.
		if ( ! isset($this->bindings[$abstract]))
		{
			return $abstract;
		}
		else
		{
			return $this->bindings[$abstract]['concrete'];
		}
	}

	/**
	 * Instantiate a concrete instance of the given type.
	 *
	 * @param  string  $concrete
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function build($concrete, $parameters = array())
	{
		// If the concrete type is actually a Closure, we will just execute it and
		// hand back the results of the functions, which allows functions to be
		// used as resolvers for more fine-tuned resolution of these objects.
		if ($concrete instanceof Closure)
		{
			return $concrete($this, $parameters);
		}

		$reflector = new ReflectionClass($concrete);

		// If the type is not instantiable, the developer is attempting to resolve
		// an abstract type such as an Interface of Abstract Class and there is
		// no binding registered for the abstractions so we need to bail out.
		if ( ! $reflector->isInstantiable())
		{
			$message = "Target [$concrete] is not instantiable.";

			throw new BindingResolutionException($message);
		}

		$constructor = $reflector->getConstructor();

		// If there are no constructors, that means there are no dependencies then
		// we can just resolve the instances of the objects right away, without
		// resolving any other types or dependencies out of these containers.
		if (is_null($constructor))
		{
			return new $concrete;
		}

		$parameters = $constructor->getParameters();

		// Once we have all the constructor's parameters we can create each of the
		// dependency instances and then use the reflection instances to make a
		// new instance of this class, injecting the created dependencies in.
		$dependencies = $this->getDependencies($parameters);

		return $reflector->newInstanceArgs($dependencies);
	}

	/**
	 * Resolve all of the dependencies from the ReflectionParameters.
	 *
	 * @param  array  $parameters
	 * @return array
	 */
	protected function getDependencies($parameters)
	{
		$dependencies = array();

		foreach ($parameters as $parameter)
		{
			$dependency = $parameter->getClass();

			// If the class is null, it means the dependency is a string or some other
			// primitive type which we can not resolve since it is not a class and
			// we'll just bomb out with an error since we have no-where to go.
			if (is_null($dependency))
			{
				$dependencies[] = $this->resolveNonClass($parameter);
			}
			else
			{
				$dependencies[] = $this->resolveClass($parameter);
			}
		}

		return (array) $dependencies;
	}

	/**
	 * Resolve a non-class hinted dependency.
	 *
	 * @param  ReflectionParameter  $parameter
	 * @return mixed
	 */
	protected function resolveNonClass(ReflectionParameter $parameter)
	{
		if ($parameter->isDefaultValueAvailable())
		{
			return $parameter->getDefaultValue();
		}
		else
		{
			$message = "Unresolvable dependency resolving [$parameter].";

			throw new BindingResolutionException($message);
		}
	}

	/**
	 * Resolve a class based dependency from the container.
	 *
	 * @param  \ReflectionParameter  $parameter
	 * @return mixed
	 */
	protected function resolveClass(ReflectionParameter $parameter)
	{
		try
		{
			return $this->make($parameter->getClass()->name);
		}

		// If we can not resolve the class instance, we will check to see if the value
		// is optional, and if it is we will return the optional parameter value as
		// the value of the dependency, similarly to how we do this with scalars.
		catch (BindingResolutionException $e)
		{
			if ($parameter->isOptional())
			{
				return $parameter->getDefaultValue();
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * Register a new resolving callback.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function resolving(Closure $callback)
	{
		$this->resolvingCallbacks[] = $callback;
	}

	/**
	 * Fire all of the resolving callbacks.
	 *
	 * @param  mixed  $object
	 * @return void
	 */
	protected function fireResolvingCallbacks($object)
	{
		foreach ($this->resolvingCallbacks as $callback)
		{
			call_user_func($callback, $object);
		}
	}

	/**
	 * Determine if a given type is shared.
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	protected function isShared($abstract)
	{
		$set = isset($this->bindings[$abstract]['shared']);

		return $set and $this->bindings[$abstract]['shared'] === true;
	}

	/**
	 * Determine if the given concrete is buildable.
	 *
	 * @param  mixed   $concrete
	 * @param  string  $abstract
	 * @return bool
	 */
	protected function isBuildable($concrete, $abstract)
	{
		return $concrete === $abstract or $concrete instanceof Closure;
	}

	/**
	 * Get the alias for an abstract if available.
	 *
	 * @param  string  $abstract
	 * @return string
	 */
	protected function getAlias($abstract)
	{
		return isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;
	}

	/**
	 * Get the container's bindings.
	 *
	 * @return array
	 */
	public function getBindings()
	{
		return $this->bindings;
	}

	/**
	 * Determine if a given offset exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return isset($this->bindings[$key]);
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->make($key);
	}

	/**
	 * Set the value at a given offset.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		// If the value is not a Closure, we will make it one. This simply gives
		// more "drop-in" replacement functionality for the Pimple which this
		// container's simplest functions are base modeled and built after.
		if ( ! $value instanceof Closure)
		{
			$value = function() use ($value)
			{
				return $value;
			};
		}

		$this->bind($key, $value);
	}

	/**
	 * Unset the value at a given offset.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->bindings[$key]);

		unset($this->instances[$key]);
	}

}